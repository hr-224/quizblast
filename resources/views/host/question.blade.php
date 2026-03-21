@extends('layouts.game')
@section('title', 'Question ' . ($game->current_question + 1))

@section('topbar-center')
  <div style="display:flex;align-items:center;gap:1.25rem">
    <div class="question-dots">
      @foreach($questions as $i => $q)
        <div class="question-dot {{ $i < $game->current_question ? 'done' : ($i == $game->current_question ? 'current' : '') }}"></div>
      @endforeach
    </div>
    <span class="stat-chip">Q<span class="val">{{ $game->current_question + 1 }}</span>/{{ $questions->count() }}</span>
    <span class="stat-chip">👥 <span class="val" id="answered-count">{{ $totalAnswered }}</span>/{{ $totalPlayers }}</span>
  </div>
@endsection

@section('topbar-right')
  <div style="display:flex;gap:.5rem">
    @if($game->status === 'reviewing')
      <form method="POST" action="{{ route('game.next', $game) }}" id="next-form" style="display:inline">
        @csrf
        @if($game->current_question + 1 >= $questions->count())
          {{-- Last question — just submit directly (auto-redirect JS handles it) --}}
          <button class="btn btn-success" id="next-btn">🏆 RESULTS</button>
        @else
          {{-- Not last — show leaderboard popup first --}}
          <button type="button" class="btn btn-success" id="next-btn" onclick="document.getElementById('lb-popup').style.display='flex';document.getElementById('lb-popup').style.animation='lb-in .4s cubic-bezier(.34,1.4,.64,1)'">NEXT →</button>
        @endif
      </form>
    @else
      <form method="POST" action="{{ route('game.skip', $game) }}" style="display:inline" onsubmit="return confirm('Skip this question?')">
        @csrf
        <button class="btn btn-outline btn-sm">⏭ SKIP</button>
      </form>
      <form method="POST" action="{{ route('game.reveal', $game) }}" id="reveal-form" style="display:inline">
        @csrf
        <button class="btn btn-primary" id="reveal-btn">REVEAL</button>
      </form>
    @endif
  </div>
@endsection

@section('content')
{{-- Question start countdown (shows on fresh page load) --}}
<div id="host-countdown" style="display:none;position:fixed;inset:0;z-index:900;background:rgba(14,11,30,.88);backdrop-filter:blur(6px);flex-direction:column;align-items:center;justify-content:center">
  <p style="font-family:'Montserrat',sans-serif;font-weight:700;font-size:.85rem;text-transform:uppercase;letter-spacing:.8px;color:rgba(255,255,255,.6);margin-bottom:1rem">Question starts in…</p>
  <div id="host-cnum" style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:8rem;line-height:1;color:var(--qb-yellow);animation:pulse-num 1s ease-in-out infinite">3</div>
</div>

{{-- 5-second reading overlay (host only) --}}
<div id="reading-overlay" style="display:none;position:fixed;inset:0;z-index:900;background:var(--qb-darker);flex-direction:column;align-items:center;justify-content:center;padding:2rem;text-align:center">
  <div style="display:flex;align-items:center;gap:1rem;margin-bottom:2rem">
    <span class="stat-chip">Q<span class="val">{{ $game->current_question + 1 }}</span>/{{ $questions->count() }}</span>
    <span style="font-size:.78rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.5px">Players are reading the question</span>
  </div>
  <div style="max-width:800px;width:100%">
    @if($question->image_url)
      <img src="{{ $question->image_url }}" style="max-height:220px;max-width:100%;border-radius:var(--radius);margin-bottom:1.5rem" />
    @elseif($question->getYoutubeId())
      <div style="position:relative;padding-bottom:38%;height:0;overflow:hidden;border-radius:var(--radius);margin-bottom:1.5rem">
        <iframe src="https://www.youtube.com/embed/{{ $question->getYoutubeId() }}?autoplay=1&mute=1" style="position:absolute;top:0;left:0;width:100%;height:100%;border:none" allowfullscreen></iframe>
      </div>
    @endif
    <h1 style="font-size:clamp(1.6rem,4vw,2.8rem);font-family:'Montserrat',sans-serif;font-weight:900;margin-bottom:2rem">{{ $question->question_text }}</h1>
  </div>
  {{-- Countdown ring --}}
  <div style="position:relative;width:90px;height:90px">
    <svg width="90" height="90" style="transform:rotate(-90deg)">
      <circle cx="45" cy="45" r="38" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="6"/>
      <circle id="ring" cx="45" cy="45" r="38" fill="none" stroke="var(--qb-yellow)" stroke-width="6"
        stroke-dasharray="239" stroke-dashoffset="239" style="transition:stroke-dashoffset 1s linear"/>
    </svg>
    <div id="reading-num" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:'Montserrat',sans-serif;font-weight:900;font-size:2rem;color:#fff">5</div>
  </div>
</div>

{{-- Post-question leaderboard overlay --}}
@if($game->status === 'reviewing' && $game->current_question + 1 < $questions->count())
<div id="lb-popup" style="display:none;position:fixed;inset:0;z-index:800;background:rgba(14,11,30,.92);backdrop-filter:blur(8px);flex-direction:column;align-items:center;justify-content:center;padding:2rem">
  <div style="width:100%;max-width:560px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
      <h2 style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1.4rem;text-transform:uppercase;letter-spacing:.5px;color:var(--qb-yellow)">🏆 Standings</h2>
      <span class="stat-chip">After Q{{ $game->current_question + 1 }}/{{ $questions->count() }}</span>
    </div>
    @php $leaders = $game->players()->where('is_spectator',false)->orderByDesc('score')->take(8)->get(); @endphp
    <ul class="leaderboard-list" style="background:var(--qb-card);border-radius:var(--radius);overflow:hidden;border:1px solid var(--qb-border)">
      @foreach($leaders as $idx => $p)
        @php $medals = ['🥇','🥈','🥉']; @endphp
        <li class="leaderboard-item" style="padding:.75rem 1.25rem;{{ !$loop->last ? 'border-bottom:1px solid var(--qb-border);' : '' }}{{ $idx === 0 ? 'background:rgba(216,158,0,.1);' : '' }}">
          <div class="leaderboard-rank" style="font-size:1.2rem;min-width:2rem">{{ $medals[$idx] ?? ($idx+1) }}</div>
          <div class="leaderboard-name" style="font-size:1rem;font-weight:700">
            {{ $p->nickname }}
            @if($p->streak >= 3)<span style="font-size:.75rem;color:var(--qb-yellow);margin-left:.4rem">🔥{{ $p->streak }}</span>@endif
          </div>
          <div class="leaderboard-score" style="font-size:1.1rem">{{ number_format($p->score) }}</div>
        </li>
      @endforeach
    </ul>
    <div style="text-align:center;margin-top:1.25rem">
      <form method="POST" action="{{ route('game.next', $game) }}">
        @csrf
        <button class="btn btn-success btn-xl">NEXT QUESTION →</button>
      </form>
    </div>
  </div>
</div>
@endif

<div id="emoji-overlay"    style="position:fixed;top:0;left:0;right:0;bottom:0;pointer-events:none;z-index:500;overflow:hidden"></div>

<div style="padding:1.5rem;max-width:960px;margin:0 auto">

  @if($game->status === 'question')
    <div style="display:flex;align-items:center;gap:1.5rem;margin-bottom:1.5rem">
      <div class="timer-number" id="timer-num">{{ $game->timeRemaining() }}</div>
      <div style="flex:1"><div class="timer-bar-wrap"><div class="timer-bar" id="timer-bar" style="width:{{ ($game->timeRemaining() / $question->time_limit) * 100 }}%"></div></div></div>
    </div>
  @else
    <div class="alert alert-info mb-3" style="text-align:center">✅ Answers revealed!</div>
  @endif

  <div class="card text-center" style="padding:2rem;margin-bottom:1.5rem;background:rgba(255,255,255,.04)">
    @if($question->image_url)
      <img src="{{ $question->image_url }}" style="max-height:200px;max-width:100%;border-radius:var(--radius);margin-bottom:1rem" />
    @elseif($question->getYoutubeId())
      <div style="position:relative;padding-bottom:38%;height:0;overflow:hidden;border-radius:var(--radius);margin-bottom:1rem">
        <iframe src="https://www.youtube.com/embed/{{ $question->getYoutubeId() }}?mute=1" style="position:absolute;top:0;left:0;width:100%;height:100%;border:none" allowfullscreen></iframe>
      </div>
    @endif
    <p style="font-size:.78rem;color:var(--qb-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5rem">Question {{ $game->current_question + 1 }}</p>
    <h2 style="font-size:clamp(1.3rem,3vw,2rem)">{{ $question->question_text }}</h2>
    @if($question->multiple_correct)
      <p style="font-size:.78rem;color:var(--qb-cyan);margin-top:.4rem;font-weight:700;text-transform:uppercase">Multiple correct answers</p>
    @endif
  </div>

  @php $shapes = ['▲','◆','●','■']; $colors = ['a0','a1','a2','a3']; @endphp
  <div class="grid-2" style="margin-bottom:1.5rem">
    @foreach($question->answers as $idx => $ans)
      <div class="answer-block {{ $colors[$idx] }} {{ $game->status === 'reviewing' ? 'answered' : '' }} {{ $game->status === 'reviewing' ? ($ans->is_correct ? 'correct' : 'incorrect') : '' }}">
        <div class="answer-shape">{{ $shapes[$idx] }}</div>
        <span style="flex:1">{{ $ans->answer_text }}</span>
        @if($game->status === 'reviewing')<span style="font-size:1.2rem">{{ $ans->is_correct ? '✓' : '✗' }}</span>@endif
      </div>
    @endforeach
  </div>

  <div class="grid-2" style="gap:1.5rem;align-items:start">
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <span style="font-weight:700;font-size:.85rem;text-transform:uppercase">Responses</span>
        <span class="stat-chip"><span class="val" id="total-answered-display">{{ $totalAnswered }}</span>/{{ $totalPlayers }} answered</span>
      </div>
    </div>

    <div class="card">
      <div style="font-weight:700;font-size:.85rem;text-transform:uppercase;margin-bottom:.75rem">Leaderboard</div>
      <ul class="leaderboard-list" id="live-lb">
        @foreach($game->players->where('is_spectator', false)->sortByDesc('score')->take(5) as $idx => $p)
          <li class="leaderboard-item">
            <div class="leaderboard-rank">{{ $idx+1 }}</div>
            <div class="leaderboard-name">{{ $p->nickname }}@if($p->streak >= 3) 🔥@endif</div>
            <div class="leaderboard-score">{{ number_format($p->score) }}</div>
          </li>
        @endforeach
      </ul>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="/js/sounds.js?v=8"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
(function(){
  const pin          = '{{ $game->pin }}';
  const appKey       = '{{ config('broadcasting.connections.reverb.key') }}';
  const wsHost       = '{{ config('broadcasting.connections.reverb.options.host') }}';
  const timeLimit    = {{ $question->time_limit }};
  const totalPlayers = {{ $totalPlayers }};
  let   timeLeft     = {{ $game->timeRemaining() }};
  let   revealed     = {{ $game->status === 'reviewing' ? 'true' : 'false' }};
  let   lastBeep     = Math.ceil(timeLeft);

  // Init audio on first click
  document.addEventListener('click',()=>{ QB.Audio.init(); QB.Audio.resume(); },{once:true});
  QB.Audio.createControls();

  const timerEl = document.getElementById('timer-num');
  const barEl   = document.getElementById('timer-bar');

  if (!revealed && timerEl) {
    const tick = setInterval(()=>{
      timeLeft = Math.max(0,timeLeft-1);
      const ceil = Math.ceil(timeLeft);
      timerEl.textContent = ceil;
      const pct = (timeLeft/timeLimit)*100;
      barEl.style.width = pct+'%';
      if(pct<=25){ barEl.classList.add('urgent'); timerEl.classList.add('urgent'); }
      if(ceil<=5&&ceil!==lastBeep){ QB.Audio.sfx.countdown(ceil); lastBeep=ceil; }
      if(timeLeft<=0){
        clearInterval(tick);
        QB.Audio.sfx.timeUp();
        document.getElementById('reveal-form')?.submit();
      }
    },1000);
  }

  function showFloatingEmoji(emoji){
    const el=document.createElement('div');
    el.style.cssText=`position:absolute;font-size:2.5rem;left:${5+Math.random()*85}%;bottom:10%;animation:emoji-float 2.5s ease-out forwards;pointer-events:none`;
    el.textContent=emoji;
    document.getElementById('emoji-overlay').appendChild(el);
    setTimeout(()=>el.remove(),2600);
  }

  const pusher = new Pusher(appKey,{wsHost,wsPort:443,wssPort:443,forceTLS:true,enabledTransports:['ws','wss'],disableStats:true,cluster:'mt1'});
  const channel = pusher.subscribe('game.'+pin);

  channel.bind('answer-count-updated',function(data){
    if(data.question_id!== {{ $question->id }}) return;
    document.getElementById('answered-count').textContent=data.total_answered;
    document.getElementById('total-answered-display').textContent=data.total_answered;
    // Auto-reveal when all players answered
    if(data.total_answered >= totalPlayers && totalPlayers > 0 && !revealed){
      revealed = true;
      document.getElementById('reveal-form').submit();
    }
  });

  channel.bind('emoji-reacted',function(data){ showFloatingEmoji(data.emoji); });
  channel.bind('power-up-used',function(data){
    const labels={double_points:'⚡ Double Points',fifty_fifty:'🎯 50/50',extra_time:'⏱ +10s'};
    const toast=document.createElement('div');
    toast.style.cssText='position:fixed;top:75px;left:50%;transform:translateX(-50%);background:var(--qb-purple3);color:#fff;padding:.5rem 1.25rem;border-radius:var(--radius);font-family:Montserrat,sans-serif;font-weight:800;font-size:.85rem;z-index:999';
    toast.textContent=`${data.nickname} used ${labels[data.type]||data.type}`;
    document.body.appendChild(toast);
    setTimeout(()=>toast.remove(),3000);
  });
  // Auto-advance to final results after last question (5s delay)
  @if($game->status === 'reviewing' && $game->current_question + 1 >= $questions->count())
  (function(){
    var bar     = document.createElement('div');
    var fill    = document.createElement('div');
    bar.style.cssText  = 'position:fixed;bottom:0;left:0;right:0;height:6px;background:rgba(255,255,255,.1);z-index:999';
    fill.style.cssText = 'height:100%;background:var(--qb-yellow);width:100%;transition:width 5s linear';
    bar.appendChild(fill);
    document.body.appendChild(bar);

    var toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;bottom:1rem;left:50%;transform:translateX(-50%);background:var(--qb-card);border:1px solid var(--qb-border);color:#fff;padding:.6rem 1.5rem;border-radius:var(--radius);font-family:Montserrat,sans-serif;font-weight:800;font-size:.85rem;z-index:999;white-space:nowrap';
    toast.textContent = '🏆 Showing results in 5…';
    document.body.appendChild(toast);

    var count = 5;
    var iv = setInterval(function(){
      count--;
      toast.textContent = '🏆 Showing results in ' + count + '…';
      if (count <= 0) {
        clearInterval(iv);
        window.location.href = '{{ route("game.final", $game) }}';
      }
    }, 1000);

    // Start drain animation
    setTimeout(function(){ fill.style.width = '0%'; }, 50);
  })();
  @endif

  // On fresh question page load, show 3,2,1 countdown
  (function(){
    @if($game->status === 'question')
    var overlay = document.getElementById('host-countdown');
    var numEl   = document.getElementById('host-cnum');
    overlay.style.display = 'flex';
    // Pause the timer bar during countdown
    var barEl  = document.getElementById('timer-bar');
    var timerEl = document.getElementById('timer-num');
    if (barEl)   barEl.style.transition = 'none';

    var count = 3;
    numEl.textContent = count;
    var iv = setInterval(function(){
      count--;
      if (count <= 0) {
        clearInterval(iv);
        overlay.style.display = 'none';
        if (barEl) barEl.style.transition = '';
      } else {
        numEl.textContent = count;
      }
    }, 1000);
    @endif
  })();
  // 5-second reading phase on new question load
  @if($game->status === 'question')
  (function(){
    var overlay = document.getElementById('reading-overlay');
    var numEl   = document.getElementById('reading-num');
    var ring    = document.getElementById('ring');
    var TOTAL   = 5;
    var count   = TOTAL;
    var CIRC    = 239;

    // Only show reading overlay on fresh question — not on reveal/back navigation
    var sessionKey = 'qb_read_q_{{ $game->id }}_{{ $game->current_question }}';
    if (!sessionStorage.getItem(sessionKey)) {
      sessionStorage.setItem(sessionKey, '1');
      overlay.style.display = 'flex';
      // Reset ring
      ring.style.transition = 'none';
      ring.style.strokeDashoffset = String(CIRC);
      setTimeout(function(){ ring.style.transition = 'stroke-dashoffset 1s linear'; }, 30);

      var hostInterval = setInterval(function() {
        count--;
        if (numEl) numEl.textContent = count;
        if (ring)  ring.style.strokeDashoffset = String(CIRC * ((TOTAL - count) / TOTAL));
        if (count <= 0) {
          clearInterval(hostInterval);
          overlay.style.display = 'none';
        }
      }, 1000);
    }
  })();
  @endif
})();
</script>
<style>
/* Hide scrollbar but keep scroll */
html { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar { display: none; }
@keyframes pulse-num { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.1);opacity:.85} }
@keyframes lb-in { from{opacity:0;transform:scale(.94)} to{opacity:1;transform:none} }
@keyframes emoji-float{0%{transform:translateY(0) scale(1);opacity:1}100%{transform:translateY(-180px) scale(1.4);opacity:0}}
</style>
@endpush
