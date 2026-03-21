#!/bin/bash
# ============================================================
#  QuizBlast — Add Player Kick Feature
#  Run: sudo bash kick-player.sh
# ============================================================
set -e
APP="/var/www/quizblast"

echo "⚡ Adding player kick feature..."

# ── 1. Add kick route ────────────────────────────────────────
sed -i "s|Route::post('/play/{pin}/leave'.*|&\nRoute::delete('/play/{pin}/kick/{player}', [PlayerController::class, 'kick'])->name('play.kick')->middleware('auth');|" \
  "$APP/routes/web.php"

echo "✓ Route added"

# ── 2. Add PlayerKicked event ────────────────────────────────
cat > "$APP/app/Events/PlayerKicked.php" << 'PHP'
<?php
namespace App\Events;

use App\Models\Game;
use App\Models\GamePlayer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerKicked implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Game $game, public int $playerId) {}

    public function broadcastOn(): array
    {
        return [new Channel('game.' . $this->game->pin)];
    }

    public function broadcastAs(): string { return 'player-kicked'; }

    public function broadcastWith(): array
    {
        return [
            'player_id' => $this->playerId,
            'count'     => $this->game->players()->count(),
            'players'   => $this->game->players()->get(['id','nickname'])->toArray(),
        ];
    }
}
PHP

echo "✓ PlayerKicked event created"

# ── 3. Add kick method to PlayerController ───────────────────
# Insert before the final closing brace of the class
sed -i "s|^}$|    public function kick(string \$pin, \$playerId)\n    {\n        \$game = \App\Models\Game::where('pin', \$pin)->where('user_id', auth()->id())->firstOrFail();\n        \$player = \App\Models\GamePlayer::where('id', \$playerId)->where('game_id', \$game->id)->firstOrFail();\n        \$player->delete();\n        broadcast(new \App\Events\PlayerKicked(\$game, (int)\$playerId));\n        broadcast(new \App\Events\PlayerLeft(\$game));\n        return response()->json(['ok' => true, 'count' => \$game->players()->count()]);\n    }\n}|" \
  "$APP/app/Http/Controllers/PlayerController.php"

echo "✓ kick() method added to PlayerController"

# ── 4. Update host lobby view ────────────────────────────────
cat > "$APP/resources/views/host/lobby.blade.php" << 'BLADE'
@extends('layouts.game')
@section('title', 'Game Lobby')

@section('topbar-center')
  <div style="text-align:center">
    <div style="font-size:0.7rem;font-weight:700;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.5px">Game PIN</div>
    <div class="pin-display" style="font-size:2rem;letter-spacing:.15em">{{ $game->pin }}</div>
  </div>
@endsection

@section('topbar-right')
  <form method="POST" action="{{ route('game.end', $game) }}">
    @csrf
    <button class="btn btn-outline btn-sm">✕ End</button>
  </form>
@endsection

@section('content')
<div style="display:flex;flex-direction:column;align-items:center;justify-content:flex-start;padding:2rem 1.5rem;min-height:calc(100vh - 61px)">

  <div style="text-align:center;margin-bottom:1.5rem">
    <p style="font-size:0.82rem;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.8px;font-weight:700;margin-bottom:0.5rem">
      Players join at <strong style="color:var(--qb-yellow)">quizblast.ultmods.com</strong> · Enter PIN
    </p>
    <div class="pin-display">{{ $game->pin }}</div>
    <div style="display:flex;align-items:center;justify-content:center;gap:0.6rem;margin-top:0.75rem">
      <div class="pulse-dot" id="ws-dot" style="background:var(--qb-muted)"></div>
      <span id="player-count" style="font-family:'Montserrat',sans-serif;font-weight:800;font-size:1rem">
        <span id="count-num">{{ $game->players->count() }}</span> player{{ $game->players->count() != 1 ? 's' : '' }} waiting
      </span>
    </div>
    <p style="font-size:0.72rem;color:rgba(255,255,255,.35);margin-top:0.4rem">Click a name to kick them</p>
  </div>

  <div id="player-grid" class="player-grid" style="max-width:900px;width:100%;margin-bottom:1.5rem"></div>

  <form method="POST" action="{{ route('game.launch', $game) }}">
    @csrf
    @if(session('error'))
      <div class="alert alert-error mb-2">{{ session('error') }}</div>
    @endif
    <button type="submit" class="btn btn-success btn-xl" id="launch-btn" {{ $game->players->isEmpty() ? 'disabled' : '' }}>
      ▶ START GAME <span style="font-size:0.82rem;opacity:.7;font-weight:600">({{ $game->quiz->questions->count() }} questions)</span>
    </button>
  </form>

  <p class="text-muted mt-2" style="font-size:0.8rem">Quiz: <strong style="color:var(--qb-text)">{{ $game->quiz->title }}</strong></p>
</div>

{{-- Kick confirm tooltip --}}
<div id="kick-confirm" style="display:none;position:fixed;z-index:999;background:#1e1e35;border:2px solid var(--qb-red);border-radius:4px;padding:0.6rem 1rem;font-size:0.82rem;font-family:'Montserrat',sans-serif;font-weight:700;box-shadow:0 4px 20px rgba(0,0,0,.5)">
  <span id="kick-name" style="color:#fff"></span>
  <span style="color:var(--qb-muted);margin:0 0.4rem">·</span>
  <span id="kick-yes" style="color:var(--qb-red);cursor:pointer;text-transform:uppercase;letter-spacing:.3px">Kick ✕</span>
  <span style="color:var(--qb-muted);margin:0 0.4rem">·</span>
  <span id="kick-no" style="color:var(--qb-muted);cursor:pointer;text-transform:uppercase;letter-spacing:.3px">Cancel</span>
</div>
@endsection

@push('head')
<style>
@keyframes float-in {
  0%   { opacity:0; transform:translateY(12px) scale(0.85); }
  100% { opacity:1; transform:translateY(0) scale(1); }
}
@keyframes float-idle {
  0%,100% { transform:translateY(0px); }
  50%     { transform:translateY(-6px); }
}
.player-chip {
  animation: float-in 0.4s cubic-bezier(.34,1.4,.64,1) forwards;
  cursor: pointer;
  transition: background 0.15s, outline 0.15s;
}
.player-chip:hover {
  background: #a0344c !important;
  outline: 2px solid var(--qb-red);
}
.player-chip.idle {
  animation: float-idle var(--dur, 3s) ease-in-out infinite;
  animation-delay: var(--delay, 0s);
}
</style>
@endpush

@push('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
(function () {
  const pin        = '{{ $game->pin }}';
  const appKey     = 'e2fed83d6d942a2471a4c131241dffaa';
  const wsHost     = 'quizblast.ultmods.com';
  const csrfToken  = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  let knownPlayers = new Set();
  let kickTarget   = null;

  // ── Kick confirm UI ──────────────────────────────────────────
  const confirmBox = document.getElementById('kick-confirm');
  const kickName   = document.getElementById('kick-name');

  document.getElementById('kick-yes').addEventListener('click', async () => {
    if (!kickTarget) return;
    confirmBox.style.display = 'none';
    try {
      await fetch(`/play/${pin}/kick/${kickTarget.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
      });
      removeChip(kickTarget.id);
    } catch(e) {}
    kickTarget = null;
  });

  document.getElementById('kick-no').addEventListener('click', () => {
    confirmBox.style.display = 'none';
    kickTarget = null;
  });

  document.addEventListener('click', (e) => {
    if (!confirmBox.contains(e.target) && !e.target.classList.contains('player-chip')) {
      confirmBox.style.display = 'none';
      kickTarget = null;
    }
  });

  // ── Chip helpers ─────────────────────────────────────────────
  function setIdle(el) {
    const dur   = (2.5 + Math.random() * 2).toFixed(1) + 's';
    const delay = (Math.random() * 2).toFixed(1) + 's';
    el.style.setProperty('--dur',   dur);
    el.style.setProperty('--delay', delay);
    setTimeout(() => el.classList.add('idle'), 420);
  }

  function addChip(id, nickname) {
    if (knownPlayers.has(String(id))) return;
    knownPlayers.add(String(id));

    const chip       = document.createElement('div');
    chip.className   = 'player-chip';
    chip.textContent = nickname;
    chip.dataset.id  = id;

    chip.addEventListener('click', (e) => {
      kickTarget = { id, nickname };
      kickName.textContent = nickname;
      confirmBox.style.display = 'block';
      confirmBox.style.left    = (e.pageX - 10) + 'px';
      confirmBox.style.top     = (e.pageY + 10) + 'px';
      e.stopPropagation();
    });

    document.getElementById('player-grid').appendChild(chip);
    setIdle(chip);
    document.getElementById('launch-btn').removeAttribute('disabled');
  }

  function removeChip(id) {
    const el = document.querySelector(`.player-chip[data-id="${id}"]`);
    if (el) {
      el.style.transition = 'opacity 0.3s, transform 0.3s';
      el.style.opacity    = '0';
      el.style.transform  = 'scale(0.7)';
      setTimeout(() => el.remove(), 300);
    }
    knownPlayers.delete(String(id));
  }

  // Init existing players
  @foreach($game->players as $p)
  addChip({{ $p->id }}, '{{ addslashes($p->nickname) }}');
  @endforeach

  // ── WebSocket ────────────────────────────────────────────────
  const pusher = new Pusher(appKey, {
    wsHost, wsPort: 443, wssPort: 443,
    forceTLS: true, enabledTransports: ['ws','wss'],
    disableStats: true, cluster: 'mt1',
  });

  pusher.connection.bind('connected', () => {
    document.getElementById('ws-dot').style.background = 'var(--qb-green)';
  });

  const channel = pusher.subscribe('game.' + pin);

  channel.bind('player-joined', function (data) {
    document.getElementById('count-num').textContent = data.count;
    addChip(data.id, data.nickname);
  });

  channel.bind('player-left', function (data) {
    document.getElementById('count-num').textContent = data.count;
    const currentIds = new Set(data.players.map(p => String(p.id)));
    [...knownPlayers].forEach(id => {
      if (!currentIds.has(id)) removeChip(id);
    });
    if (data.count === 0) document.getElementById('launch-btn').setAttribute('disabled', true);
  });

  channel.bind('player-kicked', function (data) {
    document.getElementById('count-num').textContent = data.count;
    removeChip(data.player_id);
    if (data.count === 0) document.getElementById('launch-btn').setAttribute('disabled', true);
  });

  // Fallback poll
  setInterval(async () => {
    if (pusher.connection.state !== 'connected') {
      try {
        const res  = await fetch('/api/game/' + pin + '/players');
        const data = await res.json();
        document.getElementById('count-num').textContent = data.count;
        data.players.forEach(p => addChip(p.id, p.nickname));
        if (data.count > 0) document.getElementById('launch-btn').removeAttribute('disabled');
      } catch(e) {}
    }
  }, 3000);

})();
</script>
@endpush
BLADE

echo "✓ Lobby view updated"

# ── 5. Add kicked listener to player lobby view ──────────────
# Find the game-state-changed bind and add kicked listener after it
sed -i "s|channel.bind('game-state-changed', function(data) {|channel.bind('player-kicked', function(data) {\n    if (data.player_id === {{ session('player_id_' . (isset(\$game) ? \$game->pin : '')) ?? 0 }}) {\n      window.location.href = '/play?kicked=1';\n    }\n  });\n\n  channel.bind('game-state-changed', function(data) {|g" \
  "$APP/resources/views/play/lobby.blade.php" 2>/dev/null || true

echo "✓ Player lobby updated"

# ── 6. Add kicked message to join page ──────────────────────
sed -i "s|<div class=\"card slide-up\"|@if(request('kicked'))\n        <div class=\"alert alert-error mb-2\" style=\"text-align:center\">You were kicked from the game.</div>\n        @endif\n        <div class=\"card slide-up\"|" \
  "$APP/resources/views/play/join.blade.php" 2>/dev/null || true

echo "✓ Join page updated"

# ── 7. Clear caches ──────────────────────────────────────────
cd "$APP"
php artisan view:clear
php artisan config:clear

echo ""
echo "============================================================"
echo "✅ Kick feature installed!"
echo "   Hosts can click any player name in the lobby to kick them"
echo "============================================================"
