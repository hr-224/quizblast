@extends('layouts.game')
@section('title', 'Waiting for Host')

@section('topbar-center')
  <span class="stat-chip">PIN: <span class="val">{{ $game->pin }}</span></span>
@endsection

@section('topbar-right')
  <button class="btn btn-outline btn-sm" id="leave-btn" onclick="leaveGame()">Leave</button>
@endsection

@section('content')
<div class="lobby-wrap">
  <div style="font-size:3.5rem">🎮</div>
  <h1 class="lobby-heading">You're in!</h1>
  <p class="lobby-sub">Waiting for the host to start…</p>

  <div id="player-count-pill" class="lobby-count-pill">
    <span id="lobby-count">…</span>
    <span class="lobby-count-label">players joined</span>
  </div>
  <div id="join-ticker" class="lobby-join-ticker" style="visibility:hidden">​</div>

  <div class="lobby-nick-badge">
    <p class="lobby-nick-label">Playing as</p>
    <p class="lobby-nick-name">{{ $player->nickname }}</p>
  </div>

  <div class="lobby-dots">
    <div class="pulse-dot" id="ws-dot"  style="background:var(--qb-muted)"></div>
    <div class="pulse-dot" id="ws-dot2" style="animation-delay:.2s;background:var(--qb-muted)"></div>
    <div class="pulse-dot" id="ws-dot3" style="animation-delay:.4s;background:var(--qb-muted)"></div>
    <span class="text-muted lobby-status-text" id="status-text">Connecting…</span>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
(function(){
  const pin    = '{{ $game->pin }}';
  const appKey = '{{ config('broadcasting.connections.reverb.key') }}';
  const wsHost = '{{ config('broadcasting.connections.reverb.options.host') }}';

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
      leftAlready = true;
      window.location.href = '/play?kicked=1';
    }
  });

  channel.bind('game-state-changed', function(data) {
    if (data.status === 'question' || data.status === 'reviewing') {
      leftAlready = true;
      window.location.href = '/play/' + pin + '/game';
    } else if (data.status === 'finished') {
      leftAlready = true;
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
          leftAlready = true;
          window.location.href = '/play/' + pin + '/game';
        } else if (data.status === 'finished') {
          leftAlready = true;
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

  // Leave helpers
  var leftAlready = false;
  function leaveGame() {
    if (leftAlready) return;
    leftAlready = true;
    navigator.sendBeacon('/play/' + pin + '/leave');
    window.location.href = '{{ route('play.join') }}';
  }

  // Fire leave on browser close / tab switch away
  document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'hidden' && !leftAlready) {
      navigator.sendBeacon('/play/' + pin + '/leave');
      leftAlready = true;
    }
  });
  window.addEventListener('pagehide', function() {
    if (!leftAlready) {
      navigator.sendBeacon('/play/' + pin + '/leave');
      leftAlready = true;
    }
  });

  // Initial player count
  fetch('/api/game/' + pin + '/players')
    .then(r => r.json())
    .then(data => {
      const el = document.getElementById('lobby-count');
      if (el && data.count !== undefined) el.textContent = data.count;
    }).catch(() => {});

  // Live player join/leave
  channel.bind('player-joined', function(data) {
    const countEl = document.getElementById('lobby-count');
    if (countEl && data.count !== undefined) countEl.textContent = data.count;
    showJoinTicker(data.nickname);
  });

  channel.bind('player-left', function(data) {
    const countEl = document.getElementById('lobby-count');
    if (countEl && data.count !== undefined) countEl.textContent = data.count;
    // Clear the join ticker when a player leaves
    if (tickerTimer) clearTimeout(tickerTimer);
    const ticker = document.getElementById('join-ticker');
    if (ticker) { ticker.style.opacity = '0'; ticker.style.visibility = 'hidden'; }
  });

  var tickerTimer = null;
  function showJoinTicker(nickname) {
    const el = document.getElementById('join-ticker');
    if (!el) return;
    if (tickerTimer) clearTimeout(tickerTimer);
    el.textContent = '\u2756 ' + nickname + ' just joined';
    el.style.visibility = 'visible';
    el.style.opacity = '1';
    tickerTimer = setTimeout(function() {
      el.style.opacity = '0';
      setTimeout(function() { el.style.visibility = 'hidden'; }, 400);
    }, 3000);
  }
})();
</script>
@endpush
