@extends('layouts.game')
@section('title', 'Playing')

@section('topbar-center')
  <div style="display:flex;align-items:center;gap:0.75rem">
    <span class="stat-chip">PIN: <span class="val">{{ $game->pin }}</span></span>
    <span class="stat-chip">Score: <span class="val" id="my-score">{{ $player->score }}</span></span>
    <span class="stat-chip" id="streak-chip" style="display:none">🔥 <span class="val" id="streak-num">0</span></span>
  </div>
@endsection

@section('topbar-right')
  <span style="font-family:'Montserrat',sans-serif;font-weight:700;font-size:0.9rem;color:var(--qb-yellow)">{{ $player->nickname }}</span>
@endsection

@section('content')

{{-- Reading countdown overlay --}}
<div id="reading-overlay" style="display:none;position:fixed;inset:0;z-index:900;background:var(--qb-darker);flex-direction:column;align-items:center;justify-content:center;gap:1.5rem">
  <p style="font-family:'Montserrat',sans-serif;font-weight:700;font-size:.9rem;text-transform:uppercase;letter-spacing:.8px;color:rgba(255,255,255,.5)">Look at the host screen!</p>
  <div style="position:relative;width:120px;height:120px">
    <svg width="120" height="120" style="transform:rotate(-90deg)">
      <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="7"/>
      <circle id="player-ring" cx="60" cy="60" r="50" fill="none" stroke="var(--qb-yellow)" stroke-width="7" stroke-dasharray="314" stroke-dashoffset="0"/>
    </svg>
    <div id="player-reading-num" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:'Montserrat',sans-serif;font-weight:900;font-size:3rem;color:#fff">5</div>
  </div>
</div>

{{-- Leaderboard overlay --}}
<div id="lb-overlay" style="display:none;position:fixed;inset:0;z-index:800;background:rgba(14,11,30,.92);backdrop-filter:blur(6px);flex-direction:column;align-items:center;justify-content:center;padding:2rem">
  <h2 style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1.5rem;text-transform:uppercase;letter-spacing:.5px;margin-bottom:1.5rem;color:var(--qb-yellow)">🏆 Leaderboard</h2>
  <ul class="leaderboard-list" id="overlay-lb" style="width:100%;max-width:500px"></ul>
  <p class="text-muted mt-3" style="font-size:0.82rem">Next question coming up…</p>
</div>

{{-- Bottom timer bar --}}
<div id="player-timer-wrap" style="display:none;position:fixed;bottom:0;left:0;right:0;height:10px;background:rgba(255,255,255,.1);z-index:200">
  <div id="player-timer-bar" style="height:100%;background:var(--qb-yellow);width:100%;transition:width 1s linear"></div>
</div>

{{-- Main game container --}}
<div id="game-container">

  {{-- Waiting --}}
  <div id="state-waiting" style="display:none;text-align:center;margin-top:4rem">
    <div style="font-size:3rem;margin-bottom:1rem">⏳</div>
    <h2>Get Ready!</h2>
    <p class="text-muted mt-1">The next question is coming…</p>
    <div style="display:flex;gap:.5rem;justify-content:center;margin-top:1.5rem">
      <div class="pulse-dot"></div><div class="pulse-dot" style="animation-delay:.2s"></div><div class="pulse-dot" style="animation-delay:.4s"></div>
    </div>
  </div>

  {{-- Question --}}
  <div id="state-question" style="display:none;width:100%">
    <div id="q-meta" style="display:flex;align-items:center;justify-content:space-between;padding:.5rem 1rem">
      <span class="stat-chip" id="q-progress">Q?/?</span>
      <p id="multi-hint" style="display:none;font-size:0.78rem;color:var(--qb-cyan);font-weight:700;text-transform:uppercase;letter-spacing:.5px">Select ALL correct</p>
    </div>

    {{-- Full-screen Kahoot-style answer grid --}}
    <div id="answer-grid" style="display:none;position:fixed;left:0;right:0;top:61px;bottom:0;display:grid;grid-template-columns:1fr 1fr;grid-template-rows:1fr 1fr;gap:6px;padding:6px;background:var(--qb-darker);z-index:10;touch-action:none"></div>

    {{-- Multi answer submit --}}
    <div id="multi-submit" style="display:none;position:fixed;bottom:60px;left:50%;transform:translateX(-50%);z-index:30">
      <button id="submit-multi-btn" class="btn btn-success btn-lg" onclick="submitMultiAnswer()">SUBMIT ANSWERS</button>
    </div>

    {{-- Answered waiting screen --}}
    <div id="answered-msg" style="display:none;position:fixed;inset:0;top:61px;z-index:20;background:var(--qb-darker);flex-direction:column;align-items:center;justify-content:center;gap:1rem">
      <div style="font-size:4rem" id="answered-icon">⏳</div>
      <h3 style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1.4rem;text-transform:uppercase">Answer locked in!</h3>
      <p class="text-muted">Waiting for results…</p>
    </div>

    {{-- Power-ups bar --}}
    <div id="power-ups-bar" style="display:none;position:fixed;bottom:12px;left:0;right:0;z-index:25;justify-content:center;gap:8px;padding:6px 12px">
      <button class="pu-card pu-double" id="pu-double" onclick="usePowerUp('double_points')" title="2× points if correct, LOSE points if wrong">
        <div class="pu-icon">⚡</div>
        <div class="pu-label">2× pts</div>
      </button>
      <button class="pu-card pu-fifty" id="pu-fifty" onclick="usePowerUp('fifty_fifty')" title="-50% points if correct">
        <div class="pu-icon">✂️</div>
        <div class="pu-label">50/50</div>
      </button>
      <button class="pu-card pu-spy" id="pu-spy" onclick="usePowerUp('spy')" title="-40% points if correct">
        <div class="pu-icon">🕵️</div>
        <div class="pu-label">Spy</div>
      </button>
    </div>
  </div>

  {{-- Reviewing --}}
  <div id="state-reviewing" style="display:none;width:100%;max-width:600px;text-align:center;margin:2rem auto;padding:0 1rem">
    <div style="font-size:4rem;margin-bottom:1rem" id="review-icon">—</div>
    <h2 id="review-title" style="font-size:1.8rem;margin-bottom:.25rem">—</h2>
    <p class="text-muted" id="review-points">—</p>
    <p id="review-streak" style="display:none;color:var(--qb-yellow);font-weight:700;font-size:.9rem;margin-top:.3rem"></p>
    <div style="margin-top:1.5rem" id="review-answers"></div>
    <div class="card" style="margin-top:1.5rem;padding:1.2rem">
      <div style="font-size:.75rem;color:var(--qb-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.25rem">Your Score</div>
      <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:2.5rem;color:var(--qb-yellow)" id="review-score">{{ $player->score }}</div>
    </div>
  </div>

  {{-- Finished --}}
  <div id="state-finished" style="display:none;text-align:center;margin-top:4rem">
    <div style="font-size:3.5rem;margin-bottom:1rem">🏁</div>
    <h2>Game Over!</h2>
    <p class="text-muted mt-1">Redirecting to results…</p>
  </div>

</div>
@endsection

@push('scripts')
<script src="/js/sounds.js?v=7"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
(function(){
  const pin          = '{{ $game->pin }}';
  const playerId     = {{ $player->id }};
  const csrfToken    = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const appKey       = '{{ config('broadcasting.connections.reverb.key') }}';
  const wsHost       = '{{ config('broadcasting.connections.reverb.options.host') }}';

  let currentQuestionId = null;
  let answerSubmitted   = false;
  let timerInterval     = null;
  let timeLeft          = 0;
  let timeLimit         = 0;
  let questionStartMs   = 0;
  let myScore           = {{ $player->score }};
  let myStreak          = {{ $player->streak }};
  let lastAnswerCorrect = null;
  let lastPointsEarned  = 0;
  let lastStreakBonus    = 0;
  let isMultiple        = false;
  let selectedAnswers   = new Set();
  let activePowerUp     = null;
  let usedPowerUps      = new Set({{ json_encode($player->power_ups ?? []) }});
  let lbOverlayTimer    = null;

  const SHAPES    = ['▲','◆','●','■'];
  const BG_COLORS = ['#e21b3c','#1368ce','#d89e00','#26890c'];
  const medals    = ['🥇','🥈','🥉'];

  document.addEventListener('click', () => { QB.Audio.init(); QB.Audio.resume(); }, { once: true });
  QB.Audio.createControls();

  // Prevent scroll/pull-to-refresh on mobile
  document.addEventListener('touchmove', e => { if (document.body.classList.contains('game-active')) e.preventDefault(); }, { passive: false });

  function showState(name) {
    ['waiting','question','reviewing','finished'].forEach(s => {
      const el = document.getElementById('state-' + s);
      if (el) el.style.display = s === name ? '' : 'none';
    });
  }

  function showLbOverlay(leaderboard) {
    const ol   = document.getElementById('lb-overlay');
    const list = document.getElementById('overlay-lb');
    list.innerHTML = (leaderboard || []).map((p,i) =>
      `<li class="leaderboard-item"><div class="leaderboard-rank">${medals[i]||i+1}</div><div class="leaderboard-name">${p.nickname}${p.best_streak>=3?' 🔥'+p.best_streak:''}</div><div class="leaderboard-score">${Number(p.score).toLocaleString()}</div></li>`
    ).join('');
    ol.style.display = 'flex';
    QB.Audio.sfx.leaderboard();
    if (lbOverlayTimer) clearTimeout(lbOverlayTimer);
    lbOverlayTimer = setTimeout(() => { ol.style.display = 'none'; }, 4000);
  }

  function updateScore(score) {
    myScore = score;
    const ms = document.getElementById('my-score');
    const rs = document.getElementById('review-score');
    if (ms) ms.textContent = score.toLocaleString();
    if (rs) rs.textContent = score.toLocaleString();
  }

  function updateStreak(streak) {
    myStreak = streak;
    const chip = document.getElementById('streak-chip');
    const num  = document.getElementById('streak-num');
    if (num)  num.textContent  = streak;
    if (chip) chip.style.display = streak >= 2 ? '' : 'none';
  }

  function updatePowerUpButtons() {
    [['double_points','pu-double'],['fifty_fifty','pu-fifty'],['spy','pu-spy']].forEach(([type,id]) => {
      const btn = document.getElementById(id);
      if (!btn) return;
      const avail = !usedPowerUps.has(type);
      btn.disabled      = !avail || answerSubmitted;
      btn.style.opacity = avail ? '1' : '0.35';
    });
  }

  window.usePowerUp = function(type) {
    if (usedPowerUps.has(type) || answerSubmitted) return;
    activePowerUp = type;
    usedPowerUps.add(type);
    updatePowerUpButtons();
    if (type === 'fifty_fifty') {
      const blocks = [...document.querySelectorAll('#answer-grid > div')];
      blocks.sort(() => .5 - Math.random()).slice(0,2).forEach(b => { b.style.opacity='.2'; b.style.pointerEvents='none'; });
    }
    if (type === 'spy') {
      fetch('/play/' + pin + '/spy?question_id=' + currentQuestionId, { headers: {'X-CSRF-TOKEN': csrfToken} })
        .then(r => r.json())
        .then(data => {
          const toast = document.createElement('div');
          toast.style.cssText = 'position:fixed;top:80px;left:50%;transform:translateX(-50%);background:#46178f;color:#fff;padding:.5rem 1.25rem;border-radius:4px;font-family:Montserrat,sans-serif;font-weight:800;font-size:.85rem;z-index:999';
          if (data.correct === true)       toast.textContent = '🕵 Leader answered correctly!';
          else if (data.correct === false) toast.textContent = '🕵 Leader answered incorrectly!';
          else                             toast.textContent = '🕵 Leader hasn\'t answered yet!';
          document.body.appendChild(toast);
          setTimeout(() => toast.remove(), 3000);
        }).catch(() => {});
    }
    fetch('/play/' + pin + '/powerup', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken}, body:JSON.stringify({type, question_id:currentQuestionId}) });
  };

  let currentAnswerIds = [];

  function renderAnswers(answers, disabled) {
    const grid = document.getElementById('answer-grid');
    grid.innerHTML = '';
    grid.style.display = 'grid';
    selectedAnswers.clear();
    currentAnswerIds = answers.map(a => a.id);

    answers.forEach((ans, idx) => {
      const block = document.createElement('div');
      let extra = '';
      if (ans.is_correct !== null) {
        extra = ans.is_correct ? 'filter:brightness(1.2);outline:4px solid #fff;outline-offset:-4px;' : 'filter:brightness(0.4);';
      }
      block.style.cssText = `background:${BG_COLORS[idx]};border-radius:10px;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:${disabled?'default':'pointer'};color:#fff;position:relative;user-select:none;-webkit-user-select:none;${extra}`;
      block.dataset.answerId = ans.id;

      const shape = document.createElement('div');
      shape.style.cssText = 'font-size:clamp(2.5rem,10vw,4.5rem);line-height:1;pointer-events:none';
      shape.textContent   = SHAPES[idx];

      if (ans.is_correct !== null) {
        const icon = document.createElement('div');
        icon.style.cssText = 'position:absolute;top:.5rem;right:.75rem;font-size:1.6rem';
        icon.textContent   = ans.is_correct ? '✓' : '✗';
        block.appendChild(icon);
      }
      block.appendChild(shape);

      if (!disabled && ans.is_correct === null) {
        block.addEventListener('touchstart', e => { e.preventDefault(); block.style.filter='brightness(1.3)'; }, { passive:false });
        block.addEventListener('touchend',   e => { e.preventDefault(); block.style.filter=''; if(isMultiple) toggleMulti(ans.id, block); else submitAnswer([ans.id]); }, { passive:false });
        block.addEventListener('click', () => { if(isMultiple) toggleMulti(ans.id, block); else submitAnswer([ans.id]); });
      }
      grid.appendChild(block);
    });
    document.getElementById('multi-submit').style.display = (isMultiple && !disabled) ? '' : 'none';
  }

  function toggleMulti(id, block) {
    if (answerSubmitted) return;
    if (selectedAnswers.has(id)) {
      selectedAnswers.delete(id);
      block.style.outline = '';
    } else {
      selectedAnswers.add(id);
      block.style.outline = '4px solid #fff';
      block.style.outlineOffset = '-4px';
    }
  }

  window.submitMultiAnswer = function() {
    if (selectedAnswers.size === 0) return;
    submitAnswer([...selectedAnswers]);
  };

  async function submitAnswer(answerIds) {
    if (answerSubmitted) return;
    answerSubmitted = true;
    QB.Audio.sfx.answerLocked();
    const responseTimeMs = Math.min(Date.now() - questionStartMs, timeLimit * 1000);
    document.getElementById('answer-grid').style.display   = 'none';
    document.getElementById('multi-submit').style.display  = 'none';
    document.getElementById('answered-msg').style.display  = 'flex';
    document.getElementById('power-ups-bar').style.display = 'none';
    const ptw = document.getElementById('player-timer-wrap');
    if (ptw) ptw.style.display = 'none';
    try {
      const res  = await fetch(`/play/${pin}/answer`, {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken},
        body:JSON.stringify({answer_ids:answerIds, question_id:currentQuestionId, response_time_ms:responseTimeMs, power_up:activePowerUp})
      });
      const data = await res.json();
      if (data.correct !== undefined) {
        lastAnswerCorrect = data.correct;
        lastPointsEarned  = data.points_earned;
        lastStreakBonus   = data.streak_bonus;
        updateScore(data.total_score);
        updateStreak(data.streak);
        if (data.streak >= 3) QB.Audio.sfx.streakBonus();
      }
    } catch(e) {}
  }

  function startTimer(limit, remaining) {
    clearInterval(timerInterval);
    timeLeft = remaining; timeLimit = limit;
    const pBarWrap = document.getElementById('player-timer-wrap');
    const pBar     = document.getElementById('player-timer-bar');
    let lastBeep   = Math.ceil(remaining);

    if (pBarWrap) pBarWrap.style.display = '';
    if (pBar) {
      pBar.style.transition = 'none';
      pBar.style.width      = '100%';
      pBar.style.background = 'var(--qb-yellow)';
      setTimeout(() => { if(pBar) pBar.style.transition = 'width 1s linear'; }, 30);
    }

    function tick() {
      const ceil = Math.ceil(timeLeft);
      const pct  = (timeLeft / timeLimit) * 100;
      if (pBar) {
        pBar.style.width      = Math.max(0, pct) + '%';
        pBar.style.background = pct <= 25 ? '#e21b3c' : (pct <= 50 ? '#ff8c00' : 'var(--qb-yellow)');
      }
      if (ceil <= 5 && ceil !== lastBeep) { QB.Audio.sfx.countdown(ceil); lastBeep = ceil; }
      if (timeLeft <= 0) {
        clearInterval(timerInterval);
        QB.Audio.sfx.timeUp();
        if (pBarWrap) pBarWrap.style.display = 'none';
      }
      timeLeft -= 1;
    }
    tick();
    timerInterval = setInterval(tick, 1000);
  }

  function handleStateChange(data) {
    if (data.status === 'finished') {
      clearInterval(timerInterval);
      document.body.classList.remove('game-active');
      showState('finished');
      setTimeout(() => { window.location.href = `/play/${pin}/final`; }, 2000);
      return;
    }
    if (data.status === 'waiting') { showState('waiting'); return; }

    if (data.status === 'question' && data.question) {
      const q = data.question;
      document.getElementById('lb-overlay').style.display = 'none';
      if (lbOverlayTimer) { clearTimeout(lbOverlayTimer); lbOverlayTimer = null; }

      if (currentQuestionId !== q.id) {
        currentQuestionId = q.id;
        answerSubmitted   = false;
        lastAnswerCorrect = null;
        lastPointsEarned  = 0;
        lastStreakBonus   = 0;
        activePowerUp     = null;
        isMultiple        = q.multiple_correct;

        document.getElementById('q-progress').textContent    = `Q${data.current_question+1}/${data.total_questions}`;
        document.getElementById('multi-hint').style.display  = isMultiple ? '' : 'none';
        document.getElementById('answer-grid').style.display = 'none';
        document.getElementById('answered-msg').style.display = 'none';
        document.getElementById('power-ups-bar').style.display = 'none';

        showState('question');

        // 5-second reading countdown
        const ro   = document.getElementById('reading-overlay');
        const rn   = document.getElementById('player-reading-num');
        const rr   = document.getElementById('player-ring');
        const CIRC = 314, TOTAL = 5;
        let rc     = TOTAL;

        ro.style.display = 'flex';
        rr.style.transition = 'none';
        rr.style.strokeDashoffset = '0';
        setTimeout(() => { rr.style.transition = 'stroke-dashoffset 1s linear'; }, 30);

        const readInterval = setInterval(() => {
          rc--;
          if (rn) rn.textContent = rc;
          if (rr) rr.style.strokeDashoffset = String(CIRC * ((TOTAL - rc) / TOTAL));
          if (rc <= 0) {
            clearInterval(readInterval);
            ro.style.display = 'none';
            QB.Audio.sfx.questionStart();
            questionStartMs = Date.now();
            document.getElementById('answer-grid').style.display = 'grid';
            document.getElementById('power-ups-bar').style.display = 'flex';
            document.body.classList.add('game-active');
            updatePowerUpButtons();
            renderAnswers(q.answers.map(a => ({id:a.id, text:a.text, is_correct:null})), false);
            startTimer(q.time_limit, data.time_remaining - TOTAL);
          }
        }, 1000);
      }
      return;
    }

    if (data.status === 'reviewing' && data.question) {
      clearInterval(timerInterval);
      document.body.classList.remove('game-active');
      QB.Audio.sfx.reveal();
      const q = data.question;

      document.getElementById('review-icon').textContent   = lastAnswerCorrect===null ? '⏭' : (lastAnswerCorrect ? '✅' : '❌');
      document.getElementById('review-title').textContent  = lastAnswerCorrect===null ? "Time's up!" : (lastAnswerCorrect ? 'Correct!' : 'Wrong!');
      document.getElementById('review-points').textContent = lastPointsEarned > 0
        ? `+${lastPointsEarned} points${lastStreakBonus > 0 ? ` (incl. +${lastStreakBonus} streak bonus)` : ''}`
        : (lastAnswerCorrect === false ? 'No points this round' : '');

      if (lastAnswerCorrect === true)  QB.Audio.sfx.correct();
      if (lastAnswerCorrect === false) QB.Audio.sfx.wrong();

      const streakEl = document.getElementById('review-streak');
      if (myStreak >= 3) { streakEl.textContent = `🔥 ${myStreak} answer streak!`; streakEl.style.display = ''; }
      else { streakEl.style.display = 'none'; }

      const reviewDiv = document.getElementById('review-answers');
      reviewDiv.innerHTML = '';
      const grid = document.createElement('div');
      grid.className = 'grid-2';
      q.answers.forEach((ans, idx) => {
        const block = document.createElement('div');
        block.className = 'answer-block ' + ['a0','a1','a2','a3'][idx] + ' answered ' + (ans.is_correct ? 'correct' : 'incorrect');
        block.innerHTML = `<div class="answer-shape">${SHAPES[idx]}</div><span style="flex:1">${ans.text}</span>${ans.is_correct ? '<span style="font-size:1.2rem">✓</span>' : ''}`;
        grid.appendChild(block);
      });
      reviewDiv.appendChild(grid);
      showState('reviewing');

      setTimeout(() => {
        if (data.leaderboard && data.leaderboard.length > 0) showLbOverlay(data.leaderboard);
      }, 2500);
    }
  }

  async function pollState() {
    try { const res = await fetch(`/api/game/${pin}/state`); const data = await res.json(); handleStateChange(data); } catch(e) {}
  }

  const pusher = new Pusher(appKey, { wsHost, wsPort:443, wssPort:443, forceTLS:true, enabledTransports:['ws','wss'], disableStats:true, cluster:'mt1' });
  const channel = pusher.subscribe('game.' + pin);

  channel.bind('game-state-changed', handleStateChange);
  channel.bind('player-kicked', data => { if(data.player_id == playerId) window.location.href='/play?kicked=1'; });

  setInterval(async () => { if (pusher.connection.state !== 'connected') pollState(); }, 5000);
  setInterval(() => { fetch(`/play/${pin}/heartbeat`, {method:'POST', headers:{'X-CSRF-TOKEN':csrfToken}}); }, 5000);
  pollState();
})();
</script>
<style>
body.game-active { overflow: hidden; touch-action: none; overscroll-behavior: none; }
#game-container { padding: 0.5rem 1rem; }
</style>
@endpush
