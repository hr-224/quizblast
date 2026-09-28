@extends('layouts.game')
@section('title', 'Spectating')

@section('topbar-center')
  <span class="stat-chip">👁 Spectating · PIN <span class="val">{{ $game->pin }}</span></span>
@endsection

@section('topbar-right')
  <a href="{{ route('play.join') }}" class="btn btn-outline btn-sm">Leave</a>
@endsection

@section('content')
<div id="spec-container" class="spec-container">

  <div id="spec-waiting" class="state-center">
    <div class="state-emoji" aria-hidden="true">👁</div>
    <h2>Waiting for the game to start…</h2>
    <p class="text-muted mt-1">You are spectating PIN {{ $game->pin }}</p>
    <div class="wait-dots" aria-hidden="true"><span class="pulse-dot"></span><span class="pulse-dot"></span><span class="pulse-dot"></span></div>
  </div>

  <div id="spec-question" class="spec-question hidden">
    <div class="q-meta">
      <div class="timer-ring" id="spec-bar" role="timer" aria-label="Time remaining">
        <svg width="72" height="72" viewBox="0 0 72 72" aria-hidden="true">
          <circle class="timer-ring-track" cx="36" cy="36" r="32"/>
          <circle id="spec-ring" class="timer-ring-fill" cx="36" cy="36" r="32"/>
        </svg>
        <div class="timer-ring-num timer-ring-num-sm" id="spec-timer">—</div>
      </div>
      <span class="stat-chip" id="spec-progress">Q?/?</span>
    </div>

    <div class="hq-card">
      <div id="spec-media" class="hidden">
        <img id="spec-img" class="hq-media hidden" src="" alt="" />
        <div id="spec-video" class="hq-video hidden">
          <iframe id="spec-yt" src="" title="Question video" allowfullscreen></iframe>
        </div>
      </div>
      <h2 class="hq-text" id="spec-qtext">—</h2>
    </div>

    <p class="spec-answers-wait hidden" id="spec-answers-wait">⏳ Answers appear in <span id="spec-delay-num">0</span>s</p>
    <div class="spec-answers" id="spec-answers"></div>

    <div class="card mb-3" id="spec-responses-card">
      <div class="response-chart-head">
        <span>Live responses</span>
        <span class="stat-chip"><span class="val" id="spec-answered">0</span> answered</span>
      </div>
      <div class="response-bars bars-2" id="spec-bars"></div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Leaderboard</div></div>
      <ul class="leaderboard-list" id="spec-lb"></ul>
    </div>
  </div>

  <div id="spec-reviewing" class="spec-reviewing hidden">
    <h2 class="mb-3">Answers revealed</h2>
    <div class="spec-answers mb-3" id="spec-review-answers"></div>
    <div class="card">
      <div class="card-header"><div class="card-title">Leaderboard</div></div>
      <ul class="leaderboard-list" id="spec-review-lb"></ul>
    </div>
  </div>

  <div id="spec-finished" class="state-center hidden">
    <div class="state-emoji" aria-hidden="true">🏁</div>
    <h2>Game over!</h2>
    <div class="card mt-3 spec-final-card">
      <div class="card-header"><div class="card-title">Final standings</div></div>
      <ul class="leaderboard-list" id="spec-final-lb"></ul>
    </div>
    <a href="{{ route('play.join') }}" class="btn btn-success btn-lg mt-3">Play next game</a>
  </div>

  <div id="spec-emoji-overlay" class="emoji-overlay" aria-hidden="true"></div>
</div>
@endsection

@push('scripts')
<script src="/js/shapes.js?v={{ filemtime(public_path('js/shapes.js')) }}"></script>
<script src="/js/qb-pusher.js?v={{ filemtime(public_path('js/qb-pusher.js')) }}"></script>
<script>
(function () {
  const pin    = '{{ $game->pin }}';
  const appKey = '{{ config('broadcasting.connections.reverb.key') }}';
  const wsHost = '{{ config('broadcasting.connections.reverb.options.host') }}';

  let timerInterval = null;
  let timeLeft = 0;
  let timeLimit = 0;
  const RING_CIRC = 201;   // 2 * pi * r=32, matching .timer-ring-fill's stroke-dasharray below

  function setHidden(el, hidden) { if (el) el.classList.toggle('hidden', !!hidden); }

  function showState(name) {
    ['waiting', 'question', 'reviewing', 'finished'].forEach(s => setHidden(document.getElementById('spec-' + s), s !== name));
  }

  function startTimer(limit, remaining) {
    clearInterval(timerInterval);
    timeLeft = remaining; timeLimit = limit;
    const numEl = document.getElementById('spec-timer');
    const ringEl = document.getElementById('spec-ring');
    function tick() {
      const ceil = Math.ceil(timeLeft);
      const pct = timeLeft / timeLimit;
      numEl.textContent = ceil;
      if (ringEl) ringEl.style.strokeDashoffset = String(Math.round(RING_CIRC * (1 - Math.max(0, Math.min(1, pct)))));
      if (timeLeft <= 0) clearInterval(timerInterval);
      timeLeft -= 1;
    }
    tick();
    timerInterval = setInterval(tick, 1000);
  }

  function renderLeaderboard(players, elId) {
    const el = document.getElementById(elId);
    if (!el || !players) return;
    el.textContent = '';
    const medals = ['🥇', '🥈', '🥉'];
    players.slice(0, 8).forEach((p, i) => {
      const li = document.createElement('li');
      li.className = 'leaderboard-item' + (i < 3 ? ' lb-rank-' + (i + 1) : '');
      const rank = document.createElement('div');
      rank.className = 'leaderboard-rank';
      rank.textContent = medals[i] || String(i + 1);
      const name = document.createElement('div');
      name.className = 'leaderboard-name';
      name.textContent = p.nickname;
      if (p.best_streak >= 3) {
        const badge = document.createElement('span');
        badge.className = 'lb-streak-badge';
        badge.textContent = '🔥' + p.best_streak;
        name.appendChild(badge);
      }
      const score = document.createElement('div');
      score.className = 'leaderboard-score';
      score.textContent = Number(p.score).toLocaleString();
      li.appendChild(rank); li.appendChild(name); li.appendChild(score);
      el.appendChild(li);
    });
  }

  function renderAnswerTiles(answers, containerId, reviewing) {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.textContent = '';
    answers.forEach((ans, idx) => {
      const tile = document.createElement('div');
      tile.className = 'ans-tile is-locked ans-' + (idx % 4) + (reviewing ? (ans.is_correct ? ' is-correct' : ' is-wrong') : '');
      tile.innerHTML = QB.Shapes.svg(idx);   // our own static SVG markup only — never quiz text
      const label = document.createElement('span');
      label.className = 'ans-tile-label';
      label.textContent = ans.text;
      tile.appendChild(label);
      if (reviewing && ans.is_correct) {
        const mark = document.createElement('span');
        mark.className = 'ans-tile-mark';
        mark.textContent = '✓';
        tile.appendChild(mark);
      }
      el.appendChild(tile);
    });
  }

  function renderAnswerBars(answers, answerCounts, totalPlayers) {
    const el = document.getElementById('spec-bars');
    if (!el) return;
    el.textContent = '';
    el.className = 'response-bars bars-' + answers.length;
    answers.forEach((ans, idx) => {
      const count = (answerCounts && answerCounts[ans.id]) || 0;
      const pct = totalPlayers > 0 ? Math.max(4, (count / totalPlayers) * 120) : 4;

      const col = document.createElement('div');
      col.className = 'response-col';

      const num = document.createElement('span');
      num.className = 'response-count';
      num.textContent = String(count);

      const bar = document.createElement('div');
      bar.className = 'response-bar ans-' + (idx % 4);
      bar.style.height = pct + 'px';

      const label = document.createElement('span');
      label.className = 'response-label';
      label.innerHTML = QB.Shapes.svg(idx);

      col.appendChild(num); col.appendChild(bar); col.appendChild(label);
      el.appendChild(col);
    });
  }

  function handleState(data) {
    if (data.status === 'waiting') { showState('waiting'); return; }

    if (data.status === 'question' && data.question) {
      const q = data.question;
      document.getElementById('spec-qtext').textContent = q.text;
      document.getElementById('spec-progress').textContent = `Q${data.current_question + 1}/${data.total_questions}`;

      const media = document.getElementById('spec-media');
      const img = document.getElementById('spec-img');
      const vid = document.getElementById('spec-video');
      const yt = document.getElementById('spec-yt');
      setHidden(img, true); setHidden(vid, true); setHidden(media, true);
      if (q.youtube_id) {
        yt.src = `https://www.youtube.com/embed/${q.youtube_id}?mute=1`;
        setHidden(vid, false); setHidden(media, false);
      } else if (q.image_url) {
        img.src = q.image_url;
        setHidden(img, false); setHidden(media, false);
      }

      const waitEl        = document.getElementById('spec-answers-wait');
      const answersEl      = document.getElementById('spec-answers');
      const responsesCard  = document.getElementById('spec-responses-card');
      const delayLeft       = Math.max(0, Math.ceil(data.delay_remaining || 0));

      if (delayLeft > 0) {
        setHidden(waitEl, false);
        setHidden(answersEl, true);
        setHidden(responsesCard, true);
        document.getElementById('spec-delay-num').textContent = delayLeft;
        startTimer(q.answer_delay, data.delay_remaining);
      } else {
        setHidden(waitEl, true);
        setHidden(answersEl, false);
        setHidden(responsesCard, false);
        renderAnswerTiles(q.answers, 'spec-answers', false);
        renderAnswerBars(q.answers, {}, data.player_count);
        document.getElementById('spec-answered').textContent = '0';
        startTimer(q.time_limit, data.time_remaining);
      }
      showState('question');
      return;
    }

    if (data.status === 'reviewing' && data.question) {
      clearInterval(timerInterval);
      renderAnswerTiles(data.question.answers, 'spec-review-answers', true);
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
    el.className = 'emoji-float';
    el.style.left = (5 + Math.random() * 85) + '%';
    el.textContent = emoji;
    document.getElementById('spec-emoji-overlay').appendChild(el);
    setTimeout(() => el.remove(), 2600);
  }

  async function pollState() {
    try {
      const res = await fetch(`/api/game/${pin}/state`);
      const data = await res.json();
      handleState(data);
    } catch (e) {}
  }

  // Poll immediately so the page shows something even if Pusher never loads.
  pollState();

  QB.loadPusher(Pusher => {
    try {
      if (!Pusher) throw new Error('Pusher did not load');
      const pusher = new Pusher(appKey, { wsHost, wsPort: 443, wssPort: 443, forceTLS: true, enabledTransports: ['ws', 'wss'], disableStats: true, cluster: 'mt1' });
      const channel = pusher.subscribe('game.' + pin);
      channel.bind('game-state-changed', handleState);
      channel.bind('answer-count-updated', data => {
        const countEl = document.getElementById('spec-answered');
        if (countEl) countEl.textContent = data.total_answered;
        const bars = document.getElementById('spec-bars');
        if (bars && data.answer_counts) {
          const fills = bars.querySelectorAll('.response-bar');
          const nums = bars.querySelectorAll('.response-count');
          const total = Math.max(1, data.total_answered);
          Object.values(data.answer_counts).forEach((count, idx) => {
            if (fills[idx]) fills[idx].style.height = Math.max(4, (count / total) * 120) + 'px';
            if (nums[idx]) nums[idx].textContent = String(count);
          });
        }
      });
      channel.bind('emoji-reacted', data => showFloatingEmoji(data.emoji));
      setInterval(() => { if (pusher.connection.state !== 'connected') pollState(); }, 5000);
    } catch (e) {
      // Pusher unavailable — fall back to polling every 3s
      setInterval(pollState, 3000);
    }
  });
})();
</script>
@endpush
