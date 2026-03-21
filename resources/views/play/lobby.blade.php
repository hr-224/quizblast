@extends('layouts.game')
@section('title', 'Waiting for Host')

@section('topbar-center')
  <span class="stat-chip">PIN: <span class="val">{{ $game->pin }}</span></span>
@endsection

@section('topbar-right')
  <a href="{{ route('play.join') }}" class="btn btn-outline btn-sm">Leave</a>
@endsection

@section('content')
<div style="min-height:calc(100vh - 64px);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:2rem">

  <div style="font-size:4rem;margin-bottom:1rem;animation:trophy-bounce 0.6s cubic-bezier(0.34,1.56,0.64,1)">🎮</div>
  <h1 style="font-size:2rem;margin-bottom:0.4rem">You're in!</h1>
  <p class="text-muted mb-1">Waiting for the host to start the game…</p>

  <div style="margin:1.5rem 0;padding:1rem 2.5rem;background:rgba(108,62,232,.18);border:2px solid rgba(108,62,232,.4);border-radius:var(--radius)">
    <p style="font-size:0.75rem;color:var(--qb-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:0.2rem">Playing as</p>
    <p style="font-family:'Nunito',sans-serif;font-weight:900;font-size:2rem;color:var(--qb-cyan)">{{ $player->nickname }}</p>
  </div>

  <div style="display:flex;align-items:center;gap:0.75rem;margin-top:1rem">
    <div class="pulse-dot" id="ws-dot" style="background:var(--qb-muted)"></div>
    <div class="pulse-dot" id="ws-dot2" style="animation-delay:.2s;background:var(--qb-muted)"></div>
    <div class="pulse-dot" id="ws-dot3" style="animation-delay:.4s;background:var(--qb-muted)"></div>
    <span class="text-muted" style="font-size:0.9rem" id="status-text">Connecting…</span>
  </div>

</div>
@endsection

@push('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
(function(){
  const pin    = '{{ $game->pin }}';
  const appKey = 'e2fed83d6d942a2471a4c131241dffaa';
  const wsHost = 'quizblast.ultmods.com';

  const pusher = new Pusher(appKey, {
    wsHost: wsHost, wsPort: 443, wssPort: 443,
    forceTLS: true, enabledTransports: ['ws', 'wss'],
    disableStats: true, cluster: 'mt1',
  });

  pusher.connection.bind('connected', () => {
    ['ws-dot','ws-dot2','ws-dot3'].forEach(id => {
      document.getElementById(id).style.background = 'var(--qb-green)';
    });
    document.getElementById('status-text').textContent = 'Waiting for host…';
  });

  pusher.connection.bind('disconnected', () => {
    document.getElementById('status-text').textContent = 'Reconnecting…';
  });

  const channel = pusher.subscribe('game.' + pin);

  channel.bind('player-kicked', function(data) {
    if (data.player_id == {{ session('player_id_' . $game->pin) ?? 0 }}) {
      window.location.href = '/play?kicked=1';
    }
  });

  channel.bind('game-state-changed', function(data) {
    if (data.status === 'question' || data.status === 'reviewing') {
      window.location.href = '/play/' + pin + '/game';
    } else if (data.status === 'finished') {
      window.location.href = '/play/' + pin + '/final';
    }
  });

  // Fallback poll every 5s in case WebSocket drops
  setInterval(async () => {
    if (pusher.connection.state !== 'connected') {
      try {
        const res  = await fetch('/api/game/' + pin + '/state');
        const data = await res.json();
        if (data.status === 'question' || data.status === 'reviewing') {
          window.location.href = '/play/' + pin + '/game';
        } else if (data.status === 'finished') {
          window.location.href = '/play/' + pin + '/final';
        }
      } catch(e) {}
    }
  }, 5000);

// Heartbeat every 5s
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  setInterval(() => {
    fetch('/play/' + pin + '/heartbeat', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken } });
  }, 5000);
})();
</script>
@endpush
