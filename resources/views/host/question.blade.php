@extends('layouts.game')
@section('title', 'Question ' . ($game->current_question + 1))

@php
  $isLast    = $game->current_question + 1 >= $questions->count();
  $reviewing = $game->status === 'reviewing';
  $remaining = $game->timeRemaining();
@endphp

@section('topbar-center')
  <div class="progress-dots" aria-hidden="true">
    @foreach($questions as $i => $q)
      <span class="progress-dot {{ $i < $game->current_question ? 'is-done' : ($i == $game->current_question ? 'is-current' : '') }}"></span>
    @endforeach
  </div>
  <span class="stat-chip">Q <span class="val">{{ $game->current_question + 1 }}</span>/{{ $questions->count() }}</span>
  <span class="stat-chip">👥 <span class="val" id="answered-count">{{ $totalAnswered }}</span>/{{ $totalPlayers }}</span>
@endsection

@section('topbar-right')
  <div class="host-actions">
    @if($reviewing)
      <form method="POST" action="{{ route('game.next', $game) }}" id="next-form" class="inline-form">
        @csrf
        <button type="submit" class="btn btn-success" id="next-btn">{{ $isLast ? '🏆 Results' : 'Next →' }}</button>
      </form>
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
@if(! $reviewing)
{{-- 5-second reading overlay (players are reading on their phones) --}}
<div id="reading-overlay" class="host-reading hidden">
  <div class="host-reading-top">
    <span class="stat-chip">Q <span class="val">{{ $game->current_question + 1 }}</span>/{{ $questions->count() }}</span>
    <span class="host-reading-hint">Players are reading the question</span>
  </div>
  @if($question->image_url)
    <img class="hq-media" src="{{ $question->image_url }}" alt="" />
  @endif
  <h1 class="host-reading-text">{{ $question->question_text }}</h1>
  <div class="ring">
    <svg width="120" height="120" viewBox="0 0 120 120" aria-hidden="true">
      <circle class="ring-track" cx="60" cy="60" r="50"/>
      <circle id="reading-ring" class="ring-fill" cx="60" cy="60" r="50"/>
    </svg>
    <div id="reading-num" class="ring-num">5</div>
  </div>
</div>
@endif

<div id="emoji-overlay" class="emoji-overlay" aria-hidden="true"></div>

<div class="host-question">
  <div class="hq-head">
    @if(! $reviewing)
      <div class="timer-ring" id="timer-ring" role="timer" aria-label="Time remaining">
        <svg width="96" height="96" viewBox="0 0 96 96" aria-hidden="true">
          <circle class="timer-ring-track" cx="48" cy="48" r="44"/>
          <circle id="timer-ring-fill" class="timer-ring-fill" cx="48" cy="48" r="44" stroke-dashoffset="{{ round(276 * (1 - min(1, $remaining / max(1, $question->time_limit)))) }}"/>
        </svg>
        <div class="timer-ring-num" id="timer-num">{{ $remaining }}</div>
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
        <span>{{ $totalAnswered }} responses</span>
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

  <div class="hq-answers">
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

@if($reviewing && $isLast)
  <div class="autoadvance" id="autoadvance">
    <div class="autoadvance-bar"><div class="autoadvance-fill" id="autoadvance-fill"></div></div>
    <div class="autoadvance-msg" id="autoadvance-msg">🏆 Showing results in 5…</div>
  </div>
@endif
@endsection

@push('scripts')
<script src="/js/sounds.js?v={{ filemtime(public_path('js/sounds.js')) }}"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
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
  let   revealed     = {{ $reviewing ? 'true' : 'false' }};
  let   lastBeep     = Math.ceil(timeLeft);

  document.addEventListener('click', () => { QB.Audio.init(); QB.Audio.resume(); }, { once: true });

  // Confirmation dialogs (Skip)
  document.querySelectorAll('form[data-confirm]').forEach(f => {
    f.addEventListener('submit', e => { if (!window.confirm(f.dataset.confirm)) e.preventDefault(); });
  });

  // Reveal exactly once, whether triggered by the timer, by everyone answering, or by the host.
  function submitReveal() {
    if (revealed) return;
    revealed = true;
    const f = document.getElementById('reveal-form');
    if (f) f.submit();
  }

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
  const timerNum  = document.getElementById('timer-num');
  const timerFill = document.getElementById('timer-ring-fill');
  const timerRing = document.getElementById('timer-ring');
  if (!revealed && timerNum) {
    const tick = setInterval(() => {
      timeLeft = Math.max(0, timeLeft - 1);
      const ceil = Math.ceil(timeLeft);
      const pct  = timeLeft / timeLimit;
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

  // Live events over Reverb. If js.pusher.com is blocked the timer, Reveal and Skip still work;
  // only the live "answered" counter and the all-answered auto-reveal need the socket.
  try {
    if (window.Pusher) {
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

  // 5-second reading phase on a fresh question load (not on refresh; works without sessionStorage)
  const readingOverlay = document.getElementById('reading-overlay');
  if (readingOverlay) {
    const key = 'qb_read_q_{{ $game->id }}_{{ $game->current_question }}';
    let seen = false;
    try {
      seen = !!window.sessionStorage.getItem(key);
      window.sessionStorage.setItem(key, '1');
    } catch (e) {}
    if (!seen) {
      const numEl = document.getElementById('reading-num');
      const ring  = document.getElementById('reading-ring');
      const TOTAL = 5, RCIRC = 314;
      let count = TOTAL;
      readingOverlay.classList.remove('hidden');
      ring.style.transition = 'none';
      ring.style.strokeDashoffset = '0';
      setTimeout(() => { ring.style.transition = 'stroke-dashoffset 1s linear'; }, 30);
      const iv = setInterval(() => {
        count--;
        numEl.textContent = count;
        ring.style.strokeDashoffset = String(RCIRC * ((TOTAL - count) / TOTAL));
        if (count <= 0) {
          clearInterval(iv);
          readingOverlay.classList.add('hidden');
        }
      }, 1000);
    }
  }
})();
</script>
@endpush
