@extends('layouts.game')
@section('title', 'Spectating')

@section('topbar-center')
  <span class="stat-chip">👁 SPECTATING · PIN: <span class="val">{{ $game->pin }}</span></span>
@endsection

@section('topbar-right')
  <a href="{{ route('play.join') }}" class="btn btn-outline btn-sm">Leave</a>
@endsection

@section('content')
<div id="spec-container" style="min-height:calc(100vh - 61px);display:flex;flex-direction:column;align-items:center;justify-content:flex-start;padding:1.5rem">

  <div id="spec-waiting" style="text-align:center;margin-top:4rem">
    <div style="font-size:3rem;margin-bottom:1rem">👁</div>
    <h2>Waiting for game to start…</h2>
    <p class="text-muted mt-1">You are spectating PIN {{ $game->pin }}</p>
    <div style="display:flex;gap:.5rem;justify-content:center;margin-top:1.5rem">
      <div class="pulse-dot"></div><div class="pulse-dot" style="animation-delay:.2s"></div><div class="pulse-dot" style="animation-delay:.4s"></div>
    </div>
  </div>

  <div id="spec-question" style="display:none;width:100%;max-width:860px">
    <div style="display:flex;align-items:center;gap:1.2rem;margin-bottom:1rem">
      <div class="timer-number" id="spec-timer">—</div>
      <div style="flex:1"><div class="timer-bar-wrap"><div class="timer-bar" id="spec-bar" style="width:100%"></div></div></div>
      <span class="stat-chip" id="spec-progress">Q?/?</span>
    </div>

    <div class="card text-center" style="padding:1.5rem 2rem;margin-bottom:1rem;background:rgba(255,255,255,.04)">
      <div id="spec-media" style="display:none;margin-bottom:.75rem">
        <img id="spec-img" src="" style="max-height:180px;max-width:100%;border-radius:var(--radius);display:none" />
        <div id="spec-video" style="display:none;position:relative;padding-bottom:38%;height:0;overflow:hidden;border-radius:var(--radius)">
          <iframe id="spec-yt" src="" style="position:absolute;top:0;left:0;width:100%;height:100%;border:none" allowfullscreen></iframe>
        </div>
      </div>
      <h2 style="font-size:clamp(1.1rem,3vw,1.8rem)" id="spec-qtext">—</h2>
    </div>

    <div class="grid-2" id="spec-answers" style="margin-bottom:1.5rem"></div>

    {{-- Live answer counts --}}
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <span style="font-weight:700;font-size:0.9rem">LIVE RESPONSES</span>
        <span class="stat-chip"><span class="val" id="spec-answered">0</span> answered</span>
      </div>
      <div class="answer-count-bars" id="spec-bars"></div>
    </div>

    {{-- Live leaderboard --}}
    <div class="card mt-3">
      <div class="card-header"><div class="card-title">LEADERBOARD</div></div>
      <ul class="leaderboard-list" id="spec-lb"></ul>
    </div>
  </div>

  <div id="spec-reviewing" style="display:none;width:100%;max-width:700px;text-align:center;margin-top:2rem">
    <h2 style="font-size:1.5rem;margin-bottom:1.5rem;text-transform:uppercase">Answers Revealed</h2>
    <div class="grid-2" id="spec-review-answers" style="margin-bottom:1.5rem"></div>
    <div class="card">
      <div class="card-header"><div class="card-title">LEADERBOARD</div></div>
      <ul class="leaderboard-list" id="spec-review-lb"></ul>
    </div>
  </div>

  <div id="spec-finished" style="display:none;text-align:center;margin-top:4rem">
    <div style="font-size:3.5rem;margin-bottom:1rem">🏁</div>
    <h2>Game Over!</h2>
    <div class="card mt-3" style="max-width:500px;margin:1rem auto 0">
      <div class="card-header"><div class="card-title">FINAL STANDINGS</div></div>
      <ul class="leaderboard-list" id="spec-final-lb"></ul>
    </div>
    <a href="{{ route('play.join') }}" class="btn btn-success btn-lg mt-3">PLAY NEXT GAME</a>
  </div>

  {{-- Emoji overlay --}}
  <div id="spec-emoji-overlay" style="position:fixed;top:0;left:0;right:0;bottom:0;pointer-events:none;z-index:999;overflow:hidden"></div>
</div>
@endsection

@push('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
(function(){
  const pin    = '{{ $game->pin }}';
  const appKey = 'e2fed83d6d942a2471a4c131241dffaa';
  const wsHost = 'quizblast.ultmods.com';

  const SHAPES = ['▲','◆','●','■'];
  const COLORS = ['a0','a1','a2','a3'];
  let timerInterval = null;
  let timeLeft = 0;
  let timeLimit = 0;

  function showState(name) {
    ['waiting','question','reviewing','finished'].forEach(s => {
      document.getElementById('spec-' + s).style.display = s === name ? '' : 'none';
    });
  }

  function startTimer(limit, remaining) {
    clearInterval(timerInterval);
    timeLeft = remaining; timeLimit = limit;
    const tel = document.getElementById('spec-timer');
    const bel = document.getElementById('spec-bar');
    function tick() {
      tel.textContent = Math.ceil(timeLeft);
      const pct = (timeLeft / timeLimit) * 100;
      bel.style.width = Math.max(0, pct) + '%';
      if (pct <= 25) { bel.classList.add('urgent'); tel.classList.add('urgent'); }
      if (timeLeft <= 0) clearInterval(timerInterval);
      timeLeft -= 1;
    }
    tick(); timerInterval = setInterval(tick, 1000);
  }

  function renderLeaderboard(players, elId) {
    const el = document.getElementById(elId);
    if (!el || !players) return;
    const medals = ['🥇','🥈','🥉'];
    el.innerHTML = players.slice(0, 8).map((p, i) =>
      `<li class="leaderboard-item"><div class="leaderboard-rank">${medals[i] || (i+1)}</div><div class="leaderboard-name">${p.nickname}</div><div class="leaderboard-score">${Number(p.score).toLocaleString()}</div></li>`
    ).join('');
  }

  function renderAnswerBars(answers, answerCounts, totalPlayers, containerId) {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = answers.map((ans, idx) => {
      const cnt = answerCounts?.[ans.id] ?? 0;
      const pct = totalPlayers > 0 ? (cnt / totalPlayers) * 80 : 4;
      return `<div class="count-bar-wrap">
        <div class="count-bar-num">${cnt}</div>
        <div class="count-bar-fill b${idx}" style="height:${Math.max(4, pct)}px"></div>
        <div class="count-bar-label">${SHAPES[idx]}</div>
      </div>`;
    }).join('');
  }

  function handleState(data) {
    if (data.status === 'waiting') { showState('waiting'); return; }

    if (data.status === 'question' && data.question) {
      const q = data.question;
      document.getElementById('spec-qtext').textContent = q.text;
      document.getElementById('spec-progress').textContent = `Q${data.current_question + 1}/${data.total_questions}`;

      const media = document.getElementById('spec-media');
      const img   = document.getElementById('spec-img');
      const vid   = document.getElementById('spec-video');
      const yt    = document.getElementById('spec-yt');
      img.style.display = vid.style.display = 'none'; media.style.display = 'none';
      if (q.youtube_id) { yt.src = `https://www.youtube.com/embed/${q.youtube_id}?mute=1`; vid.style.display = ''; media.style.display = ''; }
      else if (q.image_url) { img.src = q.image_url; img.style.display = ''; media.style.display = ''; }

      const grid = document.getElementById('spec-answers');
      grid.innerHTML = q.answers.map((ans, idx) =>
        `<div class="answer-block ${COLORS[idx]} answered" style="cursor:default"><div class="answer-shape">${SHAPES[idx]}</div><span style="flex:1">${ans.text}</span></div>`
      ).join('');

      renderAnswerBars(q.answers, {}, data.player_count, 'spec-bars');
      document.getElementById('spec-answered').textContent = '0';
      startTimer(q.time_limit, data.time_remaining);
      showState('question');
      return;
    }

    if (data.status === 'reviewing' && data.question) {
      clearInterval(timerInterval);
      const q = data.question;
      const grid = document.getElementById('spec-review-answers');
      grid.innerHTML = q.answers.map((ans, idx) =>
        `<div class="answer-block ${COLORS[idx]} answered ${ans.is_correct ? 'correct' : 'incorrect'}" style="cursor:default"><div class="answer-shape">${SHAPES[idx]}</div><span style="flex:1">${ans.text}</span>${ans.is_correct ? '<span>✓</span>' : ''}</div>`
      ).join('');
      if (data.leaderboard) renderLeaderboard(data.leaderboard, 'spec-review-lb');
      showState('reviewing');
      return;
    }

    if (data.status === 'finished') {
      clearInterval(timerInterval);
      if (data.leaderboard) renderLeaderboard(data.leaderboard, 'spec-final-lb');
      showState('finished');
    }
  }

  function showFloatingEmoji(emoji) {
    const el = document.createElement('div');
    el.style.cssText = `position:absolute;font-size:2.5rem;left:${5 + Math.random()*85}%;bottom:10%;animation:emoji-float 2.5s ease-out forwards;pointer-events:none`;
    el.textContent = emoji;
    document.getElementById('spec-emoji-overlay').appendChild(el);
    setTimeout(() => el.remove(), 2600);
  }

  async function pollState() {
    try {
      const res  = await fetch(`/api/game/${pin}/state`);
      const data = await res.json();
      handleState(data);
    } catch(e) {}
  }

  const pusher = new Pusher(appKey, {
    wsHost, wsPort: 443, wssPort: 443,
    forceTLS: true, enabledTransports: ['ws','wss'],
    disableStats: true, cluster: 'mt1',
  });

  const channel = pusher.subscribe('game.' + pin);
  channel.bind('game-state-changed', handleState);

  channel.bind('answer-count-updated', function(data) {
    const cnt = document.getElementById('spec-answered');
    if (cnt) cnt.textContent = data.total_answered;
    // refresh bars
    const bars = document.getElementById('spec-bars');
    if (bars && data.answer_counts) {
      const fills = bars.querySelectorAll('.count-bar-fill');
      const nums  = bars.querySelectorAll('.count-bar-num');
      const total = document.getElementById('spec-answered') ? parseInt(document.getElementById('spec-answered').textContent) : 1;
      Object.entries(data.answer_counts).forEach(([aid, cnt], idx) => {
        if (fills[idx]) fills[idx].style.height = Math.max(4, (cnt / Math.max(total, 1)) * 80) + 'px';
        if (nums[idx]) nums[idx].textContent = cnt;
      });
    }
  });

  channel.bind('emoji-reacted', function(data) {
    showFloatingEmoji(data.emoji);
  });

  setInterval(() => { if (pusher.connection.state !== 'connected') pollState(); }, 5000);
  pollState();

})();
</script>
<style>
@keyframes emoji-float {
  0%   { transform: translateY(0) scale(1); opacity: 1; }
  100% { transform: translateY(-180px) scale(1.4); opacity: 0; }
}
</style>
@endpush
