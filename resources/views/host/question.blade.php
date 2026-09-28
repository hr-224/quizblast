@extends('layouts.game')
@section('title', 'Question ' . ($game->current_question + 1))

@php
  $isLast    = $game->current_question + 1 >= $questions->count();
  $reviewing = $game->status === 'reviewing';
  $remaining = $game->timeRemaining();
  $delayRemaining = $game->delayRemaining();
  // A multi-correct question writes one GameAnswer row per selected answer, so $totalAnswered
  // (a row count) can exceed the real player count; count distinct players instead.
  $distinctAnswered = $game->gameAnswers()->where('question_id', $question->id)
                            ->distinct('game_player_id')->count('game_player_id');
@endphp

@section('topbar-center')
  <div class="progress-dots" aria-hidden="true">
    @foreach($questions as $i => $q)
      <span class="progress-dot {{ $i < $game->current_question ? 'is-done' : ($i == $game->current_question ? 'is-current' : '') }}"></span>
    @endforeach
  </div>
  <span class="stat-chip">Q <span class="val">{{ $game->current_question + 1 }}</span>/{{ $questions->count() }}</span>
  <span class="stat-chip">👥 <span class="val" id="answered-count">{{ $distinctAnswered }}</span>/{{ $totalPlayers }}</span>
@endsection

@section('topbar-right')
  <div class="host-actions">
    @if($reviewing && $isLast)
      <form method="POST" action="{{ route('game.next', $game) }}" id="next-form" class="inline-form">
        @csrf
        <button type="submit" class="btn btn-success" id="next-btn">🏆 Results</button>
      </form>
    @elseif($reviewing)
      <button type="button" class="btn btn-success" id="next-btn" aria-haspopup="dialog">Next →</button>
    @else
      <form method="POST" action="{{ route('game.skip', $game) }}" class="inline-form" data-confirm="Skip this question?">
        @csrf
        <button type="submit" class="btn btn-outline btn-sm">⏭ Skip</button>
      </form>
      <form method="POST" action="{{ route('game.reveal', $game) }}" id="reveal-form" class="inline-form">
        @csrf
        <button type="submit" class="btn btn-primary" id="reveal-btn">Reveal</button>
      </form>
    @endif
  </div>
@endsection

@section('content')
<div id="emoji-overlay" class="emoji-overlay" aria-hidden="true"></div>

<div class="host-question">
  <div class="hq-head">
    @if(! $reviewing)
      <div class="timer-ring{{ $delayRemaining > 0 ? ' timer-ring-delay' : '' }}" id="timer-ring" role="timer" aria-label="{{ $delayRemaining > 0 ? 'Time until answers appear' : 'Time remaining' }}">
        <svg width="96" height="96" viewBox="0 0 96 96" aria-hidden="true">
          <circle class="timer-ring-track" cx="48" cy="48" r="44"/>
          <circle id="timer-ring-fill" class="timer-ring-fill" cx="48" cy="48" r="44" stroke-dashoffset="{{ $delayRemaining > 0 ? round(276 * (1 - min(1, $delayRemaining / max(1, $question->answer_delay)))) : round(276 * (1 - min(1, $remaining / max(1, $question->time_limit)))) }}"/>
        </svg>
        <div class="timer-ring-num" id="timer-num">{{ $delayRemaining > 0 ? $delayRemaining : $remaining }}</div>
      </div>
    @else
      <div class="revealed-badge">✅ Answers revealed!</div>
    @endif
  </div>

  <div class="hq-card">
    @if($question->image_url)
      <img class="hq-media" src="{{ $question->image_url }}" alt="" />
    @elseif($question->getYoutubeId())
      <div class="hq-video">
        <iframe src="https://www.youtube.com/embed/{{ $question->getYoutubeId() }}?mute=1{{ $reviewing ? '' : '&autoplay=1' }}" title="Question video" allowfullscreen></iframe>
      </div>
    @endif
    <p class="hq-kicker">Question {{ $game->current_question + 1 }}</p>
    <h2 class="hq-text">{{ $question->question_text }}</h2>
    @if($question->multiple_correct)
      <p class="hq-multi">Multiple correct answers</p>
    @endif
  </div>

  @if($reviewing)
    @php $maxCount = max(1, $answerCounts->max() ?? 1); @endphp
    <div class="response-chart">
      <div class="response-chart-head">
        <span>Response breakdown</span>
        <span>{{ $distinctAnswered }} responses</span>
      </div>
      <div class="response-bars bars-{{ $question->answers->count() }}">
        @foreach($question->answers as $idx => $ans)
          @php $count = $answerCounts->get($ans->id, 0); @endphp
          <div class="response-col">
            <span class="response-count{{ $ans->is_correct ? ' is-correct' : '' }}">{{ $count }}</span>
            <div class="response-bar ans-{{ $idx % 4 }}{{ $ans->is_correct ? ' is-correct' : '' }}" style="height:{{ max(4, (int) ($count / $maxCount * 120)) }}px"></div>
            <span class="response-label{{ $ans->is_correct ? ' is-correct' : '' }}"><x-answer-shape :index="$idx" />@if($ans->is_correct)<span>✓</span>@endif</span>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  @if(! $reviewing)
    <p class="hq-answers-wait{{ $delayRemaining > 0 ? '' : ' hidden' }}" id="answers-wait-msg">⏳ Answers appear in <span id="delay-num">{{ $delayRemaining }}</span>s</p>
  @endif
  <div class="hq-answers{{ !$reviewing && $delayRemaining > 0 ? ' hidden' : '' }}" id="hq-answers">
    @foreach($question->answers as $idx => $ans)
      <div class="ans-tile host-tile is-locked ans-{{ $idx % 4 }}{{ $reviewing ? ($ans->is_correct ? ' is-correct' : ' is-wrong') : '' }}">
        <x-answer-shape :index="$idx" />
        <span class="ans-tile-label">{{ $ans->answer_text }}</span>
        @if($reviewing)
          <span class="ans-tile-mark">{{ $ans->is_correct ? '✓' : '✗' }}</span>
        @endif
      </div>
    @endforeach
  </div>
</div>

@if($reviewing && ! $isLast)
  @php
    $earned   = $game->gameAnswers()->where('question_id', $question->id)
                     ->selectRaw('game_player_id, max(points_earned) as pts')->groupBy('game_player_id')
                     ->pluck('pts', 'game_player_id');
    $standing = $game->players()->where('is_spectator', false)->orderByDesc('score')->orderBy('id')->get();
    $before   = $standing->sortByDesc(fn ($p) => $p->score - (int) $earned->get($p->id, 0))->values();
    $prevRank = $before->pluck('id')->flip()->map(fn ($i) => $i + 1);
  @endphp
  <div id="standings" class="standings hidden" role="dialog" aria-modal="true" aria-labelledby="standings-title">
    <div class="standings-panel">
      <div class="standings-head">
        <h2 class="standings-title" id="standings-title">🏆 Standings</h2>
        <span class="stat-chip">After Q{{ $game->current_question + 1 }}/{{ $questions->count() }}</span>
      </div>
      <ol class="standings-list">
        @foreach($standing->take(5) as $idx => $p)
          @php
            $rank = $idx + 1;
            $move = (int) $prevRank->get($p->id, $rank) - $rank;
            $gain = (int) $earned->get($p->id, 0);
          @endphp
          <li class="standings-row" data-move="{{ $move }}">
            <span class="standings-rank">{{ $rank }}</span>
            <span class="standings-name">{{ $p->nickname }}@if($p->streak >= 3) <span class="lb-streak-badge">🔥{{ $p->streak }}</span>@endif</span>
            @if($gain > 0)<span class="standings-gain">+{{ number_format($gain) }}</span>@endif
            <span class="standings-move {{ $move > 0 ? 'is-up' : ($move < 0 ? 'is-down' : 'is-same') }}">{{ $move > 0 ? '↑' . $move : ($move < 0 ? '↓' . abs($move) : '–') }}</span>
            <span class="standings-score">{{ number_format($p->score) }}</span>
          </li>
        @endforeach
      </ol>
      <div class="standings-actions">
        <form method="POST" action="{{ route('game.next', $game) }}">
          @csrf
          <button type="submit" class="btn btn-success btn-xl" id="standings-next">Next question →</button>
        </form>
      </div>
    </div>
  </div>
@endif

@if($reviewing && $isLast)
  <div class="autoadvance" id="autoadvance">
    <div class="autoadvance-bar"><div class="autoadvance-fill" id="autoadvance-fill"></div></div>
    <div class="autoadvance-msg" id="autoadvance-msg">🏆 Showing results in 5…</div>
  </div>
@endif
@endsection

@push('scripts')
<script src="/js/sounds.js?v={{ filemtime(public_path('js/sounds.js')) }}"></script>
<script src="/js/qb-pusher.js?v={{ filemtime(public_path('js/qb-pusher.js')) }}"></script>
<script>
(function () {
  const pin          = '{{ $game->pin }}';
  const appKey       = '{{ config('broadcasting.connections.reverb.key') }}';
  const wsHost       = '{{ config('broadcasting.connections.reverb.options.host') }}';
  const questionId   = {{ $question->id }};
  const timeLimit    = {{ $question->time_limit }};
  const totalPlayers = {{ $totalPlayers }};
  const CIRC         = 276;
  let   timeLeft     = {{ $remaining }};
  const answerDelay   = {{ $question->answer_delay }};
  let   delayLeft      = {{ $delayRemaining }};
  let   revealed     = {{ $reviewing ? 'true' : 'false' }};
  let   lastBeep     = Math.ceil(timeLeft);
  let   submitting   = false;

  // Guards every host form against a double-click (or a race between a confirmed Skip and the
  // timer's auto-reveal) locking, not submitting: the caller lets the native submit proceed.
  function lockAndSubmit(form) {
    if (submitting) return false;
    submitting = true;
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;
    return true;
  }

  document.addEventListener('click', () => { QB.Audio.init(); QB.Audio.resume(); }, { once: true });

  // Confirmation dialogs (Skip). The lock is only checked once the host has confirmed
  // (e.defaultPrevented stays false), so declining the dialog never disables the button.
  document.querySelectorAll('form[data-confirm]').forEach(f => {
    f.addEventListener('submit', e => { if (!window.confirm(f.dataset.confirm)) e.preventDefault(); else revealed = true; });
    f.addEventListener('submit', e => { if (!e.defaultPrevented) { if (!lockAndSubmit(f)) e.preventDefault(); } });
  });

  // Reveal exactly once, whether triggered by the timer, by everyone answering, or by the host.
  function submitReveal() {
    if (revealed || submitting) return;
    revealed = true;
    const f = document.getElementById('reveal-form');
    if (f && lockAndSubmit(f)) f.submit();
  }

  // A host-submitted Reveal locks the flag so the timer / all-answered paths cannot POST it again.
  const revealForm = document.getElementById('reveal-form');
  if (revealForm) revealForm.addEventListener('submit', () => { revealed = true; });
  if (revealForm) revealForm.addEventListener('submit', e => { if (!lockAndSubmit(revealForm)) e.preventDefault(); });

  // The last-question "Results" form and the standings popup's "Next question" form.
  const nextForm = document.getElementById('next-form');
  if (nextForm) nextForm.addEventListener('submit', e => { if (!lockAndSubmit(nextForm)) e.preventDefault(); });

  function showToast(text) {
    const t = document.createElement('div');
    t.className = 'toast';
    t.setAttribute('role', 'status');
    t.textContent = text;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
  }

  function showFloatingEmoji(emoji) {
    const el = document.createElement('div');
    el.className = 'emoji-float';
    el.style.left = (5 + Math.random() * 85) + '%';
    el.textContent = emoji;
    document.getElementById('emoji-overlay').appendChild(el);
    setTimeout(() => el.remove(), 2600);
  }

  // Timer — driven by the server's remaining time at page load, independent of Reverb.
  // Two phases: while delayLeft > 0, answer tiles stay hidden and the ring counts down the
  // delay; once it hits 0, the tiles are revealed and the ring switches to the answer timer.
  const timerNum       = document.getElementById('timer-num');
  const timerFill      = document.getElementById('timer-ring-fill');
  const timerRing      = document.getElementById('timer-ring');
  const answersWaitMsg = document.getElementById('answers-wait-msg');
  const hqAnswers      = document.getElementById('hq-answers');
  const delayNumEl     = document.getElementById('delay-num');
  const DELAY_CIRC     = 276;

  function startAnswerTimer() {
    if (revealed || !timerNum) return;
    const tick = setInterval(() => {
      timeLeft = Math.max(0, timeLeft - 1);
      const ceil = Math.ceil(timeLeft);
      const pct  = Math.min(1, timeLeft / timeLimit);
      timerNum.textContent = ceil;
      timerFill.style.strokeDashoffset = String(Math.round(CIRC * (1 - pct)));
      timerRing.classList.toggle('is-urgent', pct <= 0.25);
      if (ceil <= 5 && ceil !== lastBeep) { QB.Audio.sfx.countdown(ceil); lastBeep = ceil; }
      if (timeLeft <= 0) {
        clearInterval(tick);
        QB.Audio.sfx.timeUp();
        submitReveal();
      }
    }, 1000);
  }

  function revealAnswerTiles() {
    if (answersWaitMsg) answersWaitMsg.classList.add('hidden');
    if (hqAnswers) hqAnswers.classList.remove('hidden');
    if (timerRing) { timerRing.classList.remove('timer-ring-delay'); timerRing.setAttribute('aria-label', 'Time remaining'); }
    startAnswerTimer();
  }

  if (!revealed && delayLeft > 0) {
    const delayTick = setInterval(() => {
      delayLeft = Math.max(0, delayLeft - 1);
      const ceil = Math.ceil(delayLeft);
      const pct  = Math.min(1, delayLeft / answerDelay);
      if (timerNum)   timerNum.textContent = ceil;
      if (timerFill)  timerFill.style.strokeDashoffset = String(Math.round(DELAY_CIRC * (1 - pct)));
      if (delayNumEl) delayNumEl.textContent = ceil;
      if (delayLeft <= 0) {
        clearInterval(delayTick);
        revealAnswerTiles();
      }
    }, 1000);
  } else if (!revealed) {
    startAnswerTimer();
  }

  // Live events over Reverb, loaded asynchronously so a slow/blocked CDN can never hold
  // up the timer, Reveal or Skip — only the live "answered" counter and the all-answered
  // auto-reveal need the socket.
  QB.loadPusher(Pusher => {
    try {
      if (Pusher) {
        const pusher  = new Pusher(appKey, { wsHost, wsPort: 443, wssPort: 443, forceTLS: true, enabledTransports: ['ws', 'wss'], disableStats: true, cluster: 'mt1' });
        const channel = pusher.subscribe('game.' + pin);
        channel.bind('answer-count-updated', d => {
          if (d.question_id !== questionId) return;
          document.getElementById('answered-count').textContent = d.total_answered;
          if (totalPlayers > 0 && d.total_answered >= totalPlayers) submitReveal();
        });
        channel.bind('emoji-reacted', d => showFloatingEmoji(d.emoji));
        channel.bind('power-up-used', d => {
          const labels = { double_points: '⚡ Double Points', fifty_fifty: '🎯 50/50', extra_time: '⏱ +10s', spy: '🕵 Spy' };
          showToast(`${d.nickname} used ${labels[d.type] || d.type}`);
        });
      }
    } catch (e) {}
  });

  // Standings pop-up (non-last question, after the reveal)
  const standings = document.getElementById('standings');
  const nextBtn   = document.getElementById('next-btn');
  if (standings && nextBtn) {
    standings.querySelectorAll('.standings-row').forEach(row => row.style.setProperty('--move', row.dataset.move));
    const openStandings  = () => { standings.classList.remove('hidden'); document.getElementById('standings-next').focus(); };
    const closeStandings = () => { standings.classList.add('hidden'); nextBtn.focus(); };
    nextBtn.addEventListener('click', openStandings);
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !standings.classList.contains('hidden')) closeStandings(); });
    const standingsForm = document.getElementById('standings-next').closest('form');
    if (standingsForm) standingsForm.addEventListener('submit', e => { if (!lockAndSubmit(standingsForm)) e.preventDefault(); });
  }

  // Auto-advance to the final results 5s after the last question is revealed
  const advanceMsg = document.getElementById('autoadvance-msg');
  if (advanceMsg) {
    const fill = document.getElementById('autoadvance-fill');
    let count = 5;
    const iv = setInterval(() => {
      count--;
      advanceMsg.textContent = '🏆 Showing results in ' + count + '…';
      if (count <= 0) {
        clearInterval(iv);
        window.location.href = '{{ route("game.final", $game) }}';
      }
    }, 1000);
    setTimeout(() => { fill.style.width = '0%'; }, 50);
  }
})();
</script>
@endpush
