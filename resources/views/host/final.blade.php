@extends('layouts.game')
@section('title', 'Final Results')

@section('topbar-center')
  <span style="font-family:'Montserrat',sans-serif;font-weight:900;color:var(--qb-yellow)">🏆 FINAL RESULTS</span>
@endsection

@section('topbar-right')
  <div style="display:flex;gap:.5rem">
    <a href="{{ route('dashboard.history', $game) }}" class="btn btn-outline btn-sm">📊 HISTORY</a>
    <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm">← DASHBOARD</a>
  </div>
@endsection

@section('content')

{{-- Suspense reveal overlay --}}
<div id="suspense-overlay" style="display:flex;position:fixed;inset:0;z-index:999;background:rgba(14,11,30,.97);backdrop-filter:blur(12px);flex-direction:column;align-items:center;justify-content:center;overflow:hidden">
  <div style="position:absolute;inset:0;z-index:0;opacity:.15;background:radial-gradient(ellipse at center,var(--qb-purple) 0%,transparent 70%)"></div>
  <div id="suspense-content" style="position:relative;z-index:1;text-align:center;padding:2rem">
    <div id="suspense-place" style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1rem;text-transform:uppercase;letter-spacing:.8px;color:rgba(255,255,255,.5);margin-bottom:.75rem"></div>
    <div id="suspense-medal" style="font-size:clamp(5rem,15vw,10rem);line-height:1;margin-bottom:.75rem"></div>
    <div id="suspense-name"  style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:clamp(2rem,6vw,4rem);color:#fff;margin-bottom:.5rem"></div>
    <div id="suspense-score" style="font-family:'Montserrat',sans-serif;font-weight:800;font-size:clamp(1.2rem,3vw,2rem);color:var(--qb-yellow)"></div>
  </div>
</div>

{{-- Main results (hidden until suspense done) --}}
<div id="main-results" style="display:none;padding:2rem 1.5rem;max-width:720px;margin:0 auto;text-align:center">

  <div class="trophy-icon mb-2" style="font-size:4rem">🏆</div>
  <h1 style="font-size:2rem;text-transform:uppercase;margin-bottom:.25rem">Game Over!</h1>
  <p class="text-muted">{{ $game->quiz->title }}</p>

  {{-- Animated Podium --}}
  @if($players->count() >= 1)
    <div style="display:flex;align-items:flex-end;justify-content:center;gap:1.5rem;margin:2rem 0;flex-wrap:wrap">
      @php
        $podiumOrder = [1,0,2];
        $heights     = [140,185,110];
        $pmedals     = ['🥈','🥇','🥉'];
        $bgColors    = ['rgba(180,180,180,.15)','rgba(216,158,0,.22)','rgba(180,100,40,.12)'];
      @endphp
      @foreach($podiumOrder as $pi => $pos)
        @if($players->has($pos))
          @php $p = $players[$pos]; @endphp
          <div style="text-align:center;animation:podium-rise .7s cubic-bezier(.34,1.4,.64,1) {{ $pi * 0.18 }}s both">
            <div style="font-weight:800;font-size:.95rem;text-transform:uppercase;margin-bottom:.35rem;color:#fff">{{ $p->nickname }}</div>
            <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1rem;color:var(--qb-cyan);margin-bottom:.4rem">{{ number_format($p->score) }}</div>
            @if($p->best_streak >= 3)
              <div style="font-size:.72rem;color:var(--qb-yellow);margin-bottom:.4rem">🔥{{ $p->best_streak }} streak</div>
            @endif
            <div style="background:{{ $bgColors[$pi] }};border:1px solid rgba(255,255,255,.15);height:{{ $heights[$pi] }}px;width:100px;border-radius:4px 4px 0 0;display:flex;align-items:flex-start;justify-content:center;padding-top:.6rem;font-size:2.2rem">{{ $pmedals[$pi] }}</div>
          </div>
        @endif
      @endforeach
    </div>
  @endif

  {{-- Full leaderboard --}}
  @if($players->isNotEmpty())
    <div class="card" style="text-align:left;margin-bottom:1.5rem">
      <div class="card-header"><div class="card-title">FULL LEADERBOARD</div></div>
      <ul class="leaderboard-list">
        @foreach($players as $idx => $player)
          <li class="leaderboard-item">
            <div class="leaderboard-rank">
              @if($idx==0) 🥇 @elseif($idx==1) 🥈 @elseif($idx==2) 🥉 @else {{ $idx+1 }} @endif
            </div>
            <div class="leaderboard-name">
              {{ $player->nickname }}
              @if($player->best_streak >= 3)<span style="font-size:.75rem;color:var(--qb-yellow);margin-left:.4rem">🔥{{ $player->best_streak }}</span>@endif
            </div>
            <div class="leaderboard-score">{{ number_format($player->score) }}</div>
          </li>
        @endforeach
      </ul>
    </div>
  @endif

  <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap">
    <a href="{{ route('game.start', $game->quiz) }}" class="btn btn-success btn-lg">▶ PLAY AGAIN</a>
    <a href="{{ route('dashboard.history', $game) }}" class="btn btn-outline btn-lg">📊 DETAILED STATS</a>
    <a href="{{ route('quizzes.edit', $game->quiz) }}" class="btn btn-outline btn-lg">EDIT QUIZ</a>
  </div>
</div>

<style>
html { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar { display: none; }
@keyframes podium-rise { from{opacity:0;transform:translateY(40px)} to{opacity:1;transform:none} }
@keyframes suspense-in  { from{opacity:0;transform:scale(.7) translateY(30px)} to{opacity:1;transform:none} }
@keyframes suspense-out { from{opacity:1;transform:scale(1)} to{opacity:0;transform:scale(1.1)} }
</style>
@endsection

@push('scripts')
<script src="/js/sounds.js?v={{ filemtime(public_path('js/sounds.js')) }}"></script>
<script src="/js/confetti.js?v={{ filemtime(public_path('js/confetti.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  QB.Audio.init();

  @php
    $podiumData = [];
    // Build in reveal order: 3rd, 2nd, 1st (only positions that exist)
    foreach ([2, 1, 0] as $pos) {
      if ($players->has($pos)) {
        $p = $players[$pos];
        $podiumData[] = [
          'pos'   => $pos + 1,
          'name'  => $p->nickname,
          'score' => $p->score,
          'medal' => ['🥇','🥈','🥉'][$pos],
          'place' => ['1st Place','2nd Place','3rd Place'][$pos],
        ];
      }
    }
  @endphp

  var podium  = @json($podiumData);
  var overlay = document.getElementById('suspense-overlay');
  var content = document.getElementById('suspense-content');

  function showPlayer(idx) {
    if (idx >= podium.length) {
      // All revealed — fade out and show results
      overlay.style.transition = 'opacity .6s ease';
      overlay.style.opacity    = '0';
      setTimeout(function() {
        overlay.style.display = 'none';
        document.getElementById('main-results').style.display = '';
        // Fire confetti for winner
        QB.Confetti.burst(220);
        QB.Audio.sfx.podium();
        setTimeout(function(){ QB.Confetti.burst(150); }, 1200);
        setTimeout(function(){ QB.Confetti.burst(100); }, 2400);
      }, 600);
      return;
    }

    var p = podium[idx];

    // Animate out
    content.style.animation = 'suspense-out .35s ease forwards';

    setTimeout(function() {
      document.getElementById('suspense-place').textContent = p.place;
      document.getElementById('suspense-medal').textContent = p.medal;
      document.getElementById('suspense-name').textContent  = p.name;
      document.getElementById('suspense-score').textContent = Number(p.score).toLocaleString() + ' pts';

      content.style.animation = 'suspense-in .6s cubic-bezier(.34,1.4,.64,1) forwards';

      // Sound + confetti for 1st
      if (p.pos === 1) {
        QB.Confetti.burst(200);
        setTimeout(function(){ QB.Confetti.burst(120); }, 700);
      } else {
        QB.Audio.sfx.leaderboard();
      }

      // Longer pause for 1st place
      var delay = p.pos === 1 ? 4500 : 3000;
      setTimeout(function() { showPlayer(idx + 1); }, delay);
    }, 380);
  }

  // Brief pause then start reveal
  setTimeout(function() { showPlayer(0); }, 1000);
});
</script>
@endpush
