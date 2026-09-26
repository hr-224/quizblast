@extends('layouts.game')
@section('title', 'Waiting for Host')

@section('topbar-center')
  <span class="stat-chip">PIN <span class="val">{{ $game->pin }}</span></span>
@endsection

@section('topbar-right')
  <button type="button" class="btn btn-outline btn-sm" id="leave-btn">Leave</button>
@endsection

@section('content')
<div class="lobby-wrap">
  <div class="lobby-emoji" aria-hidden="true">🎮</div>
  <h1 class="lobby-heading">You're in!</h1>
  <p class="lobby-sub">Waiting for the host to start…</p>

  <div id="player-count-pill" class="lobby-count-pill">
    <span id="lobby-count">…</span>
    <span class="lobby-count-label">players joined</span>
  </div>
  <div id="join-ticker" class="lobby-join-ticker" aria-live="polite"></div>

  <div class="lobby-nick-badge">
    <p class="lobby-nick-label">Playing as</p>
    <p class="lobby-nick-name">{{ $player->nickname }}</p>
  </div>

  <div class="lobby-status" role="status">
    <span class="conn-dot" id="conn-dot"></span>
    <span id="status-text">Connecting…</span>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
(function () {
  const pin       = '{{ $game->pin }}';
  const myId      = {{ (int) session('player_id_' . $game->pin) }};
  const appKey    = '{{ config('broadcasting.connections.reverb.key') }}';
  const wsHost    = '{{ config('broadcasting.connections.reverb.options.host') }}';
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const countEl  = document.getElementById('lobby-count');
  const tickerEl = document.getElementById('join-ticker');
  const dotEl    = document.getElementById('conn-dot');
  const statusEl = document.getElementById('status-text');

  let leftAlready = false;
  let tickerTimer = null;
  let pusher      = null;

  function go(url) { leftAlready = true; window.location.href = url; }

  function routeByStatus(status) {
    if (status === 'question' || status === 'reviewing') go('/play/' + pin + '/game');
    else if (status === 'finished') go('/play/' + pin + '/final');
  }

  function setCount(n) { if (countEl && n !== undefined) countEl.textContent = n; }

  function setLive(live, text) {
    dotEl.classList.toggle('is-live', live);
    statusEl.textContent = text;
  }

  function hideTicker() {
    if (tickerTimer) clearTimeout(tickerTimer);
    tickerEl.classList.remove('is-visible');
  }

  function showJoinTicker(nickname) {
    if (tickerTimer) clearTimeout(tickerTimer);
    tickerEl.textContent = '❖ ' + nickname + ' just joined';
    tickerEl.classList.add('is-visible');
    tickerTimer = setTimeout(hideTicker, 3000);
  }

  // Live updates over Reverb. js.pusher.com may be blocked on school networks — polling below covers that.
  try {
    if (window.Pusher) {
      pusher = new Pusher(appKey, {
        wsHost: wsHost, wsPort: 443, wssPort: 443,
        forceTLS: true, enabledTransports: ['ws', 'wss'],
        disableStats: true, cluster: 'mt1',
      });
      pusher.connection.bind('connected', () => setLive(true, 'Waiting for host…'));
      pusher.connection.bind('disconnected', () => setLive(false, 'Reconnecting…'));

      const channel = pusher.subscribe('game.' + pin);
      channel.bind('player-kicked', d => { if (d.player_id == myId) go('/play?kicked=1'); });
      channel.bind('game-state-changed', d => routeByStatus(d.status));
      channel.bind('player-joined', d => { setCount(d.count); showJoinTicker(d.nickname); });
      channel.bind('player-left', d => { setCount(d.count); hideTicker(); });
    }
  } catch (e) {
    pusher = null;
  }
  if (!pusher) setLive(false, 'Waiting for host…');

  // Polling: initial count, and the fallback whenever the socket is not connected.
  async function poll() {
    try {
      const res  = await fetch('/api/game/' + pin + '/state');
      const data = await res.json();
      setCount(data.player_count);
      routeByStatus(data.status);
    } catch (e) {}
  }
  poll();
  setInterval(() => { if (!pusher || pusher.connection.state !== 'connected') poll(); }, 3000);

  // Heartbeat keeps the player from being removed as stale
  setInterval(() => {
    fetch('/play/' + pin + '/heartbeat', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken } });
  }, 5000);

  // Leaving: explicit button, and beacons when the tab is closed or hidden
  function leaveGame() {
    if (leftAlready) return;
    leftAlready = true;
    navigator.sendBeacon('/play/' + pin + '/leave');
    window.location.href = '{{ route('play.join') }}';
  }
  document.getElementById('leave-btn').addEventListener('click', leaveGame);

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden' && !leftAlready) {
      navigator.sendBeacon('/play/' + pin + '/leave');
      leftAlready = true;
    }
  });
  window.addEventListener('pagehide', () => {
    if (!leftAlready) {
      navigator.sendBeacon('/play/' + pin + '/leave');
      leftAlready = true;
    }
  });
})();
</script>
@endpush
