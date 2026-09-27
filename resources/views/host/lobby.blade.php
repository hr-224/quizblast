@extends('layouts.game')
@section('title', 'Game Lobby')

@section('topbar-center')
  <span class="stat-chip">PIN <span class="val">{{ $game->pin }}</span></span>
@endsection

@section('topbar-right')
  <form method="POST" action="{{ route('game.end', $game) }}" class="inline-form">
    @csrf
    <button type="submit" class="btn btn-outline btn-sm">✕ End</button>
  </form>
@endsection

@section('content')
@php
  $joinHost = preg_replace('#^https?://#', '', rtrim(url('/'), '/'));
  $joinable = $game->players->where('is_spectator', false);
@endphp
<div class="host-lobby">
  <p class="join-hint">Join at <strong>{{ $joinHost }}</strong> with the PIN</p>
  <div class="pin-hero" id="pin-hero" aria-label="Game PIN {{ $game->pin }}">{{ $game->pin }}</div>
  <div class="qr-card">
    <img src="https://api.qrserver.com/v1/create-qr-code/?data={{ urlencode(url('/play') . '?pin=' . $game->pin) }}&size=160x160&color=0b0a1a" width="160" height="160" alt="QR code that opens the join page for game {{ $game->pin }}" />
  </div>

  <div class="lobby-count">
    <span class="conn-dot" id="ws-dot"></span>
    <span id="player-count"><span id="count-num">{{ $joinable->count() }}</span> waiting</span>
  </div>
  <p class="lobby-hint">Select a player's name to remove them</p>

  @if(session('error'))
    <div class="alert alert-error mb-2">{{ session('error') }}</div>
  @endif

  <form method="POST" action="{{ route('game.launch', $game) }}">
    @csrf
    <button type="submit" class="btn btn-success btn-xl" id="launch-btn" {{ $joinable->isEmpty() ? 'disabled' : '' }}>
      ▶ Start game <span class="btn-sub">({{ $game->quiz->questions->count() }} questions)</span>
    </button>
  </form>

  <p class="host-meta">Quiz: <strong>{{ $game->quiz->title }}</strong></p>
  @if($game->spectator_mode)
    <p class="host-meta">Spectate: <a href="{{ route('spectate.join', $game->pin) }}" target="_blank" rel="noopener">/spectate/{{ $game->pin }}</a></p>
  @endif

  <div id="player-grid" class="host-chips" aria-label="Players"></div>
</div>

<div id="kick-confirm" class="kick-confirm hidden" role="dialog" aria-label="Remove player">
  <span id="kick-name"></span>
  <button type="button" class="btn btn-danger btn-sm" id="kick-yes">Kick</button>
  <button type="button" class="btn btn-outline btn-sm" id="kick-no">Cancel</button>
</div>
@endsection

@push('scripts')
<script src="/js/sounds.js?v={{ filemtime(public_path('js/sounds.js')) }}"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
(function () {
  const pin       = '{{ $game->pin }}';
  const appKey    = '{{ config('broadcasting.connections.reverb.key') }}';
  const wsHost    = '{{ config('broadcasting.connections.reverb.options.host') }}';
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const initial   = @json($joinable->map(fn ($p) => ['id' => $p->id, 'nickname' => $p->nickname])->values());

  const grid       = document.getElementById('player-grid');
  const countEl    = document.getElementById('count-num');
  const launchBtn  = document.getElementById('launch-btn');
  const dotEl      = document.getElementById('ws-dot');
  const confirmBox = document.getElementById('kick-confirm');
  const kickNameEl = document.getElementById('kick-name');
  const known      = new Set();
  let kickTarget   = null;
  let pusher       = null;

  // Lobby music starts on the first click (browsers block autoplay); mute is handled by the top-bar toggle.
  document.addEventListener('click', () => { QB.Audio.init(); QB.Audio.resume(); QB.Audio.playLobbyMusic(); }, { once: true });
  setTimeout(() => { if (window.QB && QB.Audio) QB.Audio.init(); }, 500);

  function sync() {
    countEl.textContent = known.size;
    launchBtn.disabled = known.size === 0;
  }

  function closeKick() {
    confirmBox.classList.add('hidden');
    kickTarget = null;
  }

  function openKick(chip, player, e) {
    kickTarget = player;
    kickNameEl.textContent = player.nickname;
    confirmBox.classList.remove('hidden');
    const r = chip.getBoundingClientRect();
    const w = confirmBox.offsetWidth;
    confirmBox.style.left = Math.max(8, Math.min(window.innerWidth - w - 8, r.left)) + 'px';
    confirmBox.style.top  = (r.bottom + 8) + 'px';
    e.stopPropagation();
  }

  function addChip(p, silent) {
    const id = String(p.id);
    if (known.has(id)) return;
    known.add(id);
    if (!silent) QB.Audio.sfx.playerJoin();
    const chip = document.createElement('button');
    chip.type = 'button';
    chip.className = 'host-chip';
    chip.textContent = p.nickname;
    chip.dataset.id = id;
    chip.addEventListener('click', e => openKick(chip, p, e));
    grid.appendChild(chip);
    setTimeout(() => chip.classList.add('is-idle'), 420);
    sync();
  }

  function removeChip(id) {
    id = String(id);
    const el = grid.querySelector('.host-chip[data-id="' + id + '"]');
    if (el) {
      el.classList.add('is-leaving');
      setTimeout(() => el.remove(), 300);
    }
    known.delete(id);
    sync();
  }

  function removeMissing(players) {
    const ids = new Set(players.map(p => String(p.id)));
    Array.from(known).forEach(id => { if (!ids.has(id)) removeChip(id); });
  }

  document.getElementById('kick-yes').addEventListener('click', async () => {
    if (!kickTarget) return;
    const target = kickTarget;
    closeKick();
    try {
      await fetch('/play/' + pin + '/kick/' + target.id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
      });
      removeChip(target.id);
    } catch (e) {}
  });
  document.getElementById('kick-no').addEventListener('click', closeKick);
  document.addEventListener('click', e => { if (!confirmBox.contains(e.target)) closeKick(); });

  initial.forEach(p => addChip(p, true));
  sync();

  // Live updates over Reverb. js.pusher.com may be blocked on school networks — polling below covers that.
  try {
    if (window.Pusher) {
      pusher = new Pusher(appKey, {
        wsHost: wsHost, wsPort: 443, wssPort: 443,
        forceTLS: true, enabledTransports: ['ws', 'wss'],
        disableStats: true, cluster: 'mt1',
      });
      pusher.connection.bind('connected', () => dotEl.classList.add('is-live'));
      pusher.connection.bind('disconnected', () => dotEl.classList.remove('is-live'));
      const channel = pusher.subscribe('game.' + pin);
      channel.bind('player-joined', d => addChip({ id: d.id, nickname: d.nickname }, false));
      channel.bind('player-left', d => removeMissing(d.players));
      channel.bind('player-kicked', d => removeChip(d.player_id));
    }
  } catch (e) {
    pusher = null;
  }

  // Polling fallback whenever the socket is not connected (also the only source without Pusher).
  setInterval(async () => {
    if (pusher && pusher.connection.state === 'connected') return;
    try {
      const res  = await fetch('/api/game/' + pin + '/players');
      const data = await res.json();
      removeMissing(data.players);
      data.players.forEach(p => addChip(p, false));
    } catch (e) {}
  }, 5000);
})();
</script>
@endpush
