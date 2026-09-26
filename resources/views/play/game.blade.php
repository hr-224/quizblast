@extends('layouts.game')
@section('title', 'Playing')

@section('topbar-center')
  <span class="stat-chip">PIN <span class="val">{{ $game->pin }}</span></span>
  <span class="stat-chip">Score <span class="val" id="my-score">{{ $player->score }}</span></span>
  <span class="stat-chip hidden" id="streak-chip">🔥 <span class="val" id="streak-num">0</span></span>
@endsection

@section('topbar-right')
  <span class="topbar-nick">{{ $player->nickname }}</span>
@endsection

@section('content')

{{-- 5-second reading countdown --}}
<div id="reading-overlay" class="reading-overlay hidden" role="status" aria-live="polite">
  <p class="reading-hint">Look at the host screen!</p>
  <div class="ring">
    <svg width="120" height="120" viewBox="0 0 120 120" aria-hidden="true">
      <circle class="ring-track" cx="60" cy="60" r="50"/>
      <circle id="player-ring" class="ring-fill" cx="60" cy="60" r="50"/>
    </svg>
    <div id="player-reading-num" class="ring-num">5</div>
  </div>
</div>

<div id="game-container">

  {{-- Waiting --}}
  <div id="state-waiting" class="state-center hidden">
    <div class="state-emoji" aria-hidden="true">⏳</div>
    <h2>Get ready!</h2>
    <p class="text-muted mt-1">The next question is coming…</p>
    <div class="wait-dots" aria-hidden="true"><span class="pulse-dot"></span><span class="pulse-dot"></span><span class="pulse-dot"></span></div>
  </div>

  {{-- Question --}}
  <div id="state-question" class="q-screen hidden">
    <div id="player-timer-wrap" class="q-timer hidden"><div id="player-timer-bar" class="q-timer-bar"></div></div>

    <div id="q-meta" class="q-meta">
      <span class="stat-chip" id="q-progress">Q?/?</span>
      <p id="multi-hint" class="multi-hint hidden">Select all correct answers</p>
    </div>

    <div id="answer-grid" class="answer-grid hidden" role="group" aria-label="Answer choices"></div>

    <div id="answered-msg" class="q-locked hidden">
      <div id="answered-block" class="answered-block-preview ans-tile is-locked"></div>
      <h3 class="q-locked-title">Locked in!</h3>
      <p class="text-muted">Waiting for the host to reveal…</p>
      <div class="answered-score-badge">
        <div class="answered-score-label">Current score</div>
        <div class="answered-score-val" id="answered-score-display">0</div>
      </div>
    </div>

    <div id="player-tray" class="player-tray hidden">
      <button type="button" id="submit-multi-btn" class="btn btn-success btn-lg hidden">Submit answers</button>
      <div id="power-ups-bar" class="pu-row">
        <button type="button" class="pu-card pu-double" id="pu-double" title="2× points if correct — you lose points if wrong">
          <div class="pu-icon">⚡</div>
          <div class="pu-label">2× pts</div>
        </button>
        <button type="button" class="pu-card pu-fifty" id="pu-fifty" title="Removes two wrong answers — half points if correct">
          <div class="pu-icon">✂️</div>
          <div class="pu-label">50/50</div>
        </button>
        <button type="button" class="pu-card pu-spy" id="pu-spy" title="Peek at whether the leader got it right — 60% points if correct">
          <div class="pu-icon">🕵️</div>
          <div class="pu-label">Spy</div>
        </button>
      </div>
    </div>
  </div>

  {{-- Reviewing --}}
  <div id="state-reviewing" class="review-screen hidden">
    <div id="review-verdict-card" class="review-verdict-card review-verdict-neutral">
      <div class="review-icon" id="review-icon">—</div>
      <h2 class="review-title" id="review-title">—</h2>
      <p class="review-points" id="review-points">—</p>
      <p class="review-streak hidden" id="review-streak"></p>
      <p class="review-rank hidden" id="review-rank"></p>
    </div>
    <div id="review-answers" class="review-answers"></div>
    <div class="card review-score-card">
      <div class="review-score-label">Your score</div>
      <div class="review-score" id="review-score">{{ $player->score }}</div>
    </div>
  </div>

  {{-- Finished --}}
  <div id="state-finished" class="state-center hidden">
    <div class="state-emoji" aria-hidden="true">🏁</div>
    <h2>Game over!</h2>
    <p class="text-muted mt-1">Redirecting to results…</p>
  </div>

</div>
@endsection

@push('scripts')
<script src="/js/shapes.js?v={{ filemtime(public_path('js/shapes.js')) }}"></script>
<script src="/js/sounds.js?v={{ filemtime(public_path('js/sounds.js')) }}"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
(function(){
  const pin        = '{{ $game->pin }}';
  const playerId   = {{ $player->id }};
  const myNickname = @json($player->nickname);
  const csrfToken  = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const appKey     = '{{ config('broadcasting.connections.reverb.key') }}';
  const wsHost     = '{{ config('broadcasting.connections.reverb.options.host') }}';

  let currentQuestionId = null;
  let answerSubmitted   = false;
  let timerInterval     = null;
  let readInterval      = null;
  let timeLeft          = 0;
  let timeLimit         = 0;
  let questionStartMs   = 0;
  let myScore           = {{ $player->score }};
  let scoreBefore       = myScore;
  let myStreak          = {{ $player->streak }};
  let lastAnswerCorrect = null;
  let lastPointsEarned  = 0;
  let lastStreakBonus   = 0;
  let isMultiple        = false;
  let activePowerUp     = null;
  let currentAnswerIds  = [];
  let reviewSoundPlayed = false;
  let reviewingActive   = false;
  let lastLeaderboard   = null;
  const selectedAnswers = new Set();
  const usedPowerUps    = new Set(@json($player->power_ups ?? []));

  const POWER_UPS = [['double_points', 'pu-double'], ['fifty_fifty', 'pu-fifty'], ['spy', 'pu-spy']];

  function setHidden(el, hidden) { if (el) el.classList.toggle('hidden', !!hidden); }
  function isCalm() { return window.QB && QB.UI && QB.UI.fx() === 'calm'; }

  // Play the correct/wrong sound once both conditions are met:
  // (1) the reviewing state is on screen, (2) the server has returned the result.
  // Called from both the reviewing handler and submitAnswer — whichever arrives last wins.
  function playAnswerResultSound() {
    if (reviewSoundPlayed || !reviewingActive || lastAnswerCorrect === null) return;
    reviewSoundPlayed = true;
    if (lastAnswerCorrect === true)  QB.Audio.sfx.correct();
    if (lastAnswerCorrect === false) QB.Audio.sfx.wrong();
  }

  document.addEventListener('click', () => { QB.Audio.init(); QB.Audio.resume(); }, { once: true });

  // Prevent scroll/pull-to-refresh on mobile while a question is live
  document.addEventListener('touchmove', e => { if (document.body.classList.contains('game-active')) e.preventDefault(); }, { passive: false });

  function showState(name) {
    ['waiting', 'question', 'reviewing', 'finished'].forEach(s => setHidden(document.getElementById('state-' + s), s !== name));
  }

  function countUp(el, from, to) {
    if (!el) return;
    if (from === to || isCalm()) { el.textContent = to.toLocaleString(); return; }
    const start = Date.now(), dur = 700;
    (function step() {
      const p = Math.min(1, (Date.now() - start) / dur);
      el.textContent = Math.round(from + (to - from) * p).toLocaleString();
      if (p < 1) requestAnimationFrame(step);
    })();
  }

  function showToast(text) {
    const t = document.createElement('div');
    t.className = 'toast';
    t.setAttribute('role', 'status');
    t.textContent = text;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
  }

  function updateScore(score) {
    myScore = score;
    const ms = document.getElementById('my-score');
    if (ms) ms.textContent = score.toLocaleString();
  }

  function updateStreak(streak) {
    myStreak = streak;
    const num = document.getElementById('streak-num');
    if (num) num.textContent = streak;
    setHidden(document.getElementById('streak-chip'), streak < 2);
  }

  function updatePowerUpButtons() {
    POWER_UPS.forEach(([type, id]) => {
      const btn = document.getElementById(id);
      if (!btn) return;
      const avail = !usedPowerUps.has(type);
      btn.disabled = !avail || answerSubmitted;
      btn.classList.toggle('is-used', !avail);
    });
  }

  function usePowerUp(type) {
    if (usedPowerUps.has(type) || answerSubmitted) return;
    activePowerUp = type;
    usedPowerUps.add(type);
    updatePowerUpButtons();
    if (type === 'spy') {
      fetch('/play/' + pin + '/spy?question_id=' + currentQuestionId, { headers: { 'X-CSRF-TOKEN': csrfToken } })
        .then(r => r.json())
        .then(data => {
          if (data.correct === true)       showToast('🕵 Leader answered correctly!');
          else if (data.correct === false) showToast('🕵 Leader answered incorrectly!');
          else                             showToast("🕵 Leader hasn't answered yet!");
        }).catch(() => {});
    }
    fetch('/play/' + pin + '/powerup', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ type, question_id: currentQuestionId }),
    })
      .then(r => r.json())
      .then(data => {
        if (type === 'fifty_fifty' && data.eliminate && data.eliminate.length) {
          const gone = new Set(data.eliminate.map(Number));
          document.querySelectorAll('#answer-grid .ans-tile').forEach(tile => {
            if (gone.has(Number(tile.dataset.answerId))) tile.classList.add('is-eliminated');
          });
        }
      }).catch(() => {});
  }

  function renderAnswers(answers) {
    const grid = document.getElementById('answer-grid');
    grid.textContent = '';
    selectedAnswers.clear();
    currentAnswerIds = answers.map(a => a.id);

    answers.forEach((ans, idx) => {
      const tile = document.createElement('button');
      tile.type = 'button';
      tile.className = 'ans-tile ans-' + (idx % 4);
      tile.dataset.answerId = ans.id;
      tile.setAttribute('aria-label', ans.text);
      if (isMultiple) tile.setAttribute('aria-pressed', 'false');
      tile.innerHTML = QB.Shapes.svg(idx);   // our own static SVG only — never quiz text
      tile.addEventListener('click', () => {
        if (isMultiple) toggleMulti(ans.id, tile); else submitAnswer([ans.id]);
      });
      grid.appendChild(tile);
    });
    setHidden(grid, false);
  }

  function toggleMulti(id, tile) {
    if (answerSubmitted) return;
    if (selectedAnswers.has(id)) {
      selectedAnswers.delete(id);
      tile.classList.remove('is-selected');
      tile.setAttribute('aria-pressed', 'false');
    } else {
      selectedAnswers.add(id);
      tile.classList.add('is-selected');
      tile.setAttribute('aria-pressed', 'true');
    }
    document.getElementById('submit-multi-btn').disabled = selectedAnswers.size === 0;
  }

  function renderVerdict() {
    const isPartial = lastAnswerCorrect === 'partial';
    document.getElementById('review-icon').textContent  = lastAnswerCorrect === null ? '⏭' : (lastAnswerCorrect === true ? '✅' : (isPartial ? '⭐' : '❌'));
    document.getElementById('review-title').textContent = lastAnswerCorrect === null ? "Time's up!" : (lastAnswerCorrect === true ? 'Correct!' : (isPartial ? 'Partial Credit!' : 'Wrong!'));
    document.getElementById('review-verdict-card').className = 'review-verdict-card ' +
      (lastAnswerCorrect === true  ? 'review-verdict-correct'  :
       isPartial                   ? 'review-verdict-partial'  :
       lastAnswerCorrect === false ? 'review-verdict-wrong'    : 'review-verdict-neutral');

    let pointsText = '';
    if (lastPointsEarned > 0) {
      pointsText = `+${lastPointsEarned} points${lastStreakBonus > 0 ? ` (incl. +${lastStreakBonus} streak bonus)` : ''}`;
    } else if (lastPointsEarned < 0) {
      pointsText = `${lastPointsEarned} points (double points backfired)`;
    } else if (lastAnswerCorrect === false) {
      pointsText = 'No points this round';
    }
    document.getElementById('review-points').textContent = pointsText;

    const streakEl = document.getElementById('review-streak');
    if (myStreak >= 3) { streakEl.textContent = `🔥 ${myStreak} answer streak!`; setHidden(streakEl, false); }
    else { setHidden(streakEl, true); }

    countUp(document.getElementById('review-score'), scoreBefore, myScore);
  }

  function renderRank() {
    const el = document.getElementById('review-rank');
    let rank = 0;
    if (lastLeaderboard) {
      for (let i = 0; i < lastLeaderboard.length; i++) {
        if (lastLeaderboard[i].nickname === myNickname) { rank = i + 1; break; }
      }
    }
    if (rank) { el.textContent = `You're #${rank}`; setHidden(el, false); }
    else { setHidden(el, true); }
  }

  async function submitAnswer(answerIds) {
    if (answerSubmitted) return;
    answerSubmitted = true;
    QB.Audio.sfx.answerLocked();
    const responseTimeMs = Math.min(Date.now() - questionStartMs, timeLimit * 1000);

    const tappedIdx = currentAnswerIds.indexOf(answerIds[0]);
    const ab = document.getElementById('answered-block');
    if (ab && tappedIdx >= 0) {
      ab.className = 'answered-block-preview ans-tile is-locked ans-' + (tappedIdx % 4);
      ab.innerHTML = QB.Shapes.svg(tappedIdx);
    }
    document.getElementById('answered-score-display').textContent = myScore.toLocaleString();
    setHidden(document.getElementById('answer-grid'), true);
    setHidden(document.getElementById('player-tray'), true);
    setHidden(document.getElementById('player-timer-wrap'), true);
    setHidden(document.getElementById('answered-msg'), false);

    try {
      const res  = await fetch(`/play/${pin}/answer`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ answer_ids: answerIds, question_id: currentQuestionId, response_time_ms: responseTimeMs, power_up: activePowerUp }),
      });
      const data = await res.json();
      if (data.correct !== undefined) {
        lastAnswerCorrect = data.correct;
        lastPointsEarned  = data.points_earned;
        lastStreakBonus   = data.streak_bonus;
        updateScore(data.total_score);
        updateStreak(data.streak);
        if (data.streak >= 3) QB.Audio.sfx.streakBonus();
        playAnswerResultSound();
        if (reviewingActive) renderVerdict();
      }
    } catch (e) {}
  }

  function startTimer(limit, remaining) {
    clearInterval(timerInterval);
    timeLeft = remaining; timeLimit = limit;
    const wrap = document.getElementById('player-timer-wrap');
    const bar  = document.getElementById('player-timer-bar');
    let lastBeep = Math.ceil(remaining);

    setHidden(wrap, false);
    bar.classList.remove('is-warn', 'is-urgent');
    bar.style.transition = 'none';
    bar.style.width = '100%';
    setTimeout(() => { bar.style.transition = ''; }, 30);

    function tick() {
      const ceil = Math.ceil(timeLeft);
      const pct  = (timeLeft / timeLimit) * 100;
      bar.style.width = Math.max(0, pct) + '%';
      bar.classList.toggle('is-warn', pct <= 50 && pct > 25);
      bar.classList.toggle('is-urgent', pct <= 25);
      if (ceil <= 5 && ceil !== lastBeep) { QB.Audio.sfx.countdown(ceil); lastBeep = ceil; }
      if (timeLeft <= 0) {
        clearInterval(timerInterval);
        QB.Audio.sfx.timeUp();
        setHidden(wrap, true);
      }
      timeLeft -= 1;
    }
    tick();
    timerInterval = setInterval(tick, 1000);
  }

  function startReading(q, data) {
    const overlay = document.getElementById('reading-overlay');
    const num     = document.getElementById('player-reading-num');
    const ring    = document.getElementById('player-ring');
    const CIRC = 314, TOTAL = 5;
    let rc = TOTAL;

    setHidden(overlay, false);
    num.textContent = rc;
    ring.style.transition = 'none';
    ring.style.strokeDashoffset = '0';
    setTimeout(() => { ring.style.transition = 'stroke-dashoffset 1s linear'; }, 30);

    clearInterval(readInterval);
    readInterval = setInterval(() => {
      rc--;
      num.textContent = rc;
      ring.style.strokeDashoffset = String(CIRC * ((TOTAL - rc) / TOTAL));
      if (rc <= 0) {
        clearInterval(readInterval);
        setHidden(overlay, true);
        QB.Audio.sfx.questionStart();
        questionStartMs = Date.now();
        document.body.classList.add('game-active');
        renderAnswers(q.answers);
        const submit = document.getElementById('submit-multi-btn');
        setHidden(submit, !isMultiple);
        submit.disabled = true;
        updatePowerUpButtons();
        setHidden(document.getElementById('player-tray'), false);
        startTimer(q.time_limit, data.time_remaining - TOTAL);
      }
    }, 1000);
  }

  function handleStateChange(data) {
    if (data.status === 'finished') {
      clearInterval(timerInterval);
      clearInterval(readInterval);
      setHidden(document.getElementById('reading-overlay'), true);
      document.body.classList.remove('game-active');
      showState('finished');
      setTimeout(() => { window.location.href = `/play/${pin}/final`; }, 2000);
      return;
    }
    if (data.status === 'waiting') { showState('waiting'); return; }

    if (data.status === 'question' && data.question) {
      const q = data.question;
      if (currentQuestionId !== q.id) {
        clearInterval(timerInterval);
        currentQuestionId = q.id;
        answerSubmitted   = false;
        lastAnswerCorrect = null;
        lastPointsEarned  = 0;
        lastStreakBonus   = 0;
        activePowerUp     = null;
        reviewSoundPlayed = false;
        reviewingActive   = false;
        isMultiple        = q.multiple_correct;
        scoreBefore       = myScore;

        document.getElementById('q-progress').textContent = `Q${data.current_question + 1}/${data.total_questions}`;
        setHidden(document.getElementById('multi-hint'), !isMultiple);
        setHidden(document.getElementById('answer-grid'), true);
        setHidden(document.getElementById('answered-msg'), true);
        setHidden(document.getElementById('player-tray'), true);
        setHidden(document.getElementById('player-timer-wrap'), true);
        showState('question');
        startReading(q, data);
      }
      return;
    }

    if (data.status === 'reviewing' && data.question) {
      clearInterval(timerInterval);
      clearInterval(readInterval);
      setHidden(document.getElementById('reading-overlay'), true);
      document.body.classList.remove('game-active');
      if (!reviewingActive) QB.Audio.sfx.reveal();
      lastLeaderboard = data.leaderboard || null;
      reviewingActive = true;
      renderVerdict();
      renderRank();
      playAnswerResultSound();

      const reviewDiv = document.getElementById('review-answers');
      reviewDiv.textContent = '';
      data.question.answers.forEach((ans, idx) => {
        const tile = document.createElement('div');
        tile.className = 'ans-tile is-locked ans-' + (idx % 4) + (ans.is_correct ? ' is-correct' : ' is-wrong');
        tile.innerHTML = QB.Shapes.svg(idx);
        const label = document.createElement('span');
        label.className = 'ans-tile-label';
        label.textContent = ans.text;
        tile.appendChild(label);
        if (ans.is_correct) {
          const mark = document.createElement('span');
          mark.className = 'ans-tile-mark';
          mark.textContent = '✓';
          tile.appendChild(mark);
        }
        reviewDiv.appendChild(tile);
      });
      showState('reviewing');
    }
  }

  document.getElementById('submit-multi-btn').addEventListener('click', () => {
    if (selectedAnswers.size > 0) submitAnswer([...selectedAnswers]);
  });
  POWER_UPS.forEach(([type, id]) => {
    document.getElementById(id).addEventListener('click', () => usePowerUp(type));
  });

  async function pollState() {
    try { const res = await fetch(`/api/game/${pin}/state`); const data = await res.json(); handleStateChange(data); } catch (e) {}
  }

  updateStreak(myStreak);

  // Poll immediately so the UI shows something even if Pusher fails to load
  pollState();

  try {
    const pusher  = new Pusher(appKey, { wsHost, wsPort: 443, wssPort: 443, forceTLS: true, enabledTransports: ['ws', 'wss'], disableStats: true, cluster: 'mt1' });
    const channel = pusher.subscribe('game.' + pin);
    channel.bind('game-state-changed', handleStateChange);
    channel.bind('player-kicked', data => { if (data.player_id == playerId) window.location.href = '/play?kicked=1'; });
    setInterval(async () => { if (pusher.connection.state !== 'connected') pollState(); }, 5000);
  } catch (e) {
    // Pusher unavailable — fall back to polling every 3s
    setInterval(pollState, 3000);
  }
  setInterval(() => { fetch(`/play/${pin}/heartbeat`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken } }); }, 5000);
})();
</script>
@endpush
