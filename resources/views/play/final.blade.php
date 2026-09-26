@extends('layouts.game')
@section('title', 'Results')

@section('topbar-center')
  <span style="font-family:'Montserrat',sans-serif;font-weight:900;color:var(--qb-yellow)">🏆 FINAL RESULTS</span>
@endsection

@section('topbar-right')
  <a href="{{ route('play.join') }}" class="btn btn-outline btn-sm">PLAY AGAIN</a>
@endsection

@section('content')

{{-- Suspense reveal overlay --}}
<div id="suspense-overlay" style="display:flex;position:fixed;inset:0;z-index:999;background:rgba(14,11,30,.97);backdrop-filter:blur(12px);flex-direction:column;align-items:center;justify-content:center;overflow:hidden">
  <div id="suspense-bg-blur" style="position:absolute;inset:0;z-index:0;opacity:0.18;background:radial-gradient(ellipse at center, var(--qb-purple) 0%, transparent 70%)"></div>
  <div id="suspense-content" style="position:relative;z-index:1;text-align:center;padding:2rem;animation:suspense-in .6s cubic-bezier(.34,1.4,.64,1) both">
    <div id="suspense-place" style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1rem;text-transform:uppercase;letter-spacing:.8px;color:rgba(255,255,255,.5);margin-bottom:.75rem"></div>
    <div id="suspense-medal" style="font-size:clamp(4rem,15vw,8rem);line-height:1;margin-bottom:.75rem"></div>
    <div id="suspense-name"  style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:clamp(1.8rem,6vw,3rem);color:#fff;margin-bottom:.5rem"></div>
    <div id="suspense-score" style="font-family:'Montserrat',sans-serif;font-weight:800;font-size:clamp(1.2rem,4vw,1.8rem);color:var(--qb-yellow)"></div>
    @if(isset($rank) && $rank <= 3)
      <div id="suspense-you" style="display:none;margin-top:.75rem;padding:.35rem .9rem;background:var(--qb-purple3);border-radius:4px;font-family:'Montserrat',sans-serif;font-weight:800;font-size:.85rem;color:#fff;display:inline-block">That's you! 🎉</div>
    @endif
  </div>
</div>

{{-- Main results (hidden until suspense done) --}}
<div id="main-results" style="display:none;min-height:calc(100vh - 61px);padding:2rem 1.5rem;max-width:700px;margin:0 auto">

  @if($player)
    <div style="text-align:center;margin-bottom:2rem">
      <div style="font-size:{{ $rank <= 3 ? '5rem' : '3rem' }}">
        @if($rank == 1) 🥇 @elseif($rank == 2) 🥈 @elseif($rank == 3) 🥉 @else 🎮 @endif
      </div>
      <h1 style="font-size:clamp(1.6rem,4vw,2.2rem);text-transform:uppercase;margin-bottom:.25rem">{{ $player->nickname }}</h1>
      <p class="text-muted">#{{ $rank }} of {{ $players->count() }} players</p>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin:1.25rem auto;max-width:420px">
        <div class="card" style="padding:1rem;text-align:center">
          <div style="font-size:.7rem;color:var(--qb-muted);text-transform:uppercase;margin-bottom:.25rem">Score</div>
          <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1.5rem;color:var(--qb-yellow)">{{ number_format($player->score) }}</div>
        </div>
        <div class="card" style="padding:1rem;text-align:center">
          <div style="font-size:.7rem;color:var(--qb-muted);text-transform:uppercase;margin-bottom:.25rem">Best Streak</div>
          <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1.5rem;color:var(--qb-cyan)">🔥{{ $player->best_streak }}</div>
        </div>
        @if($personalStats)
          <div class="card" style="padding:1rem;text-align:center">
            <div style="font-size:.7rem;color:var(--qb-muted);text-transform:uppercase;margin-bottom:.25rem">Accuracy</div>
            <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1.5rem;color:#5ddd3a">{{ $personalStats['accuracy'] }}%</div>
          </div>
        @endif
      </div>
    </div>
  @endif

  {{-- Animated Podium --}}
  @if($players->count() >= 1)
    <div style="display:flex;align-items:flex-end;justify-content:center;gap:1rem;margin-bottom:2rem">
      @php
        $podiumOrder = [1,0,2];
        $heights     = [130,170,105];
        $pmedals     = ['🥈','🥇','🥉'];
        $bgColors    = ['rgba(180,180,180,.15)','rgba(216,158,0,.2)','rgba(180,100,40,.12)'];
      @endphp
      @foreach($podiumOrder as $pi => $pos)
        @if($players->has($pos))
          @php $p = $players[$pos]; @endphp
          <div style="text-align:center;animation:podium-rise .7s cubic-bezier(.34,1.4,.64,1) {{ $pi * 0.2 }}s both">
            <div style="font-weight:800;font-size:.85rem;text-transform:uppercase;margin-bottom:.3rem;{{ (isset($player) && $p->id === $player->id) ? 'color:var(--qb-yellow)' : '' }}">{{ $p->nickname }}</div>
            <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:.9rem;color:var(--qb-cyan);margin-bottom:.4rem">{{ number_format($p->score) }}</div>
            <div style="background:{{ $bgColors[$pi] }};border:1px solid rgba(255,255,255,.15);height:{{ $heights[$pi] }}px;width:85px;border-radius:4px 4px 0 0;display:flex;align-items:flex-start;justify-content:center;padding-top:.5rem;font-size:1.8rem">{{ $pmedals[$pi] }}</div>
          </div>
        @endif
      @endforeach
    </div>
  @endif

  {{-- Leaderboard --}}
  <div class="card mb-3">
    <div class="card-header"><div class="card-title">LEADERBOARD</div></div>
    <ul class="leaderboard-list">
      @foreach($players as $idx => $p)
        <li class="leaderboard-item" style="{{ (isset($player) && $p->id === $player->id) ? 'border-color:rgba(108,62,232,.5);background:rgba(108,62,232,.12)' : '' }}">
          <div class="leaderboard-rank">@if($idx==0) 🥇 @elseif($idx==1) 🥈 @elseif($idx==2) 🥉 @else {{ $idx+1 }} @endif</div>
          <div class="leaderboard-name">
            {{ $p->nickname }}
            @if($p->best_streak >= 3)<span style="font-size:.75rem;color:var(--qb-yellow);margin-left:.4rem">🔥{{ $p->best_streak }}</span>@endif
            @if(isset($player) && $p->id === $player->id)<span style="color:var(--qb-purple3);font-size:.75rem;margin-left:.4rem">(you)</span>@endif
          </div>
          <div class="leaderboard-score">{{ number_format($p->score) }}</div>
        </li>
      @endforeach
    </ul>
  </div>

  @if($personalStats)
    <div class="card mb-3">
      <div class="card-header"><div class="card-title">YOUR PERFORMANCE</div></div>
      <div style="margin-bottom:1rem;display:flex;gap:1rem;flex-wrap:wrap">
        <span class="stat-chip">Correct: <span class="val">{{ $personalStats['correct'] }}/{{ $personalStats['total'] }}</span></span>
        <span class="stat-chip">Accuracy: <span class="val">{{ $personalStats['accuracy'] }}%</span></span>
        <span class="stat-chip">Best Streak: <span class="val">🔥{{ $personalStats['best_streak'] }}</span></span>
      </div>
      @foreach($personalStats['questions'] as $idx => $stat)
        @php
          $statBg     = $stat['correct'] ? 'rgba(38,137,12,0.3)' : 'rgba(226,27,60,0.3)';
          $statColor  = $stat['correct'] ? '#5ddd3a' : 'var(--qb-muted)';
          $statIcon   = $stat['correct'] ? '✓' : '✗';
          $statBorder = $loop->last ? '' : 'border-bottom:1px solid var(--qb-border);';
        @endphp
        <div style="display:flex;align-items:center;gap:.75rem;padding:.6rem 0;{{ $statBorder }}">
          <div style="width:22px;height:22px;border-radius:3px;background:{{ $statBg }};display:flex;align-items:center;justify-content:center;font-size:.75rem;flex-shrink:0">{{ $statIcon }}</div>
          <div style="flex:1;font-size:.82rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">Q{{ $idx+1 }}: {{ $stat['question'] }}</div>
          <div style="font-family:'Montserrat',sans-serif;font-weight:800;font-size:.82rem;color:{{ $statColor }};flex-shrink:0">+{{ number_format($stat['points']) }}</div>
          @if($stat['time_ms'])
            <div style="font-size:.75rem;color:var(--qb-muted);flex-shrink:0">{{ round($stat['time_ms']/1000,1) }}s</div>
          @endif
        </div>
      @endforeach
    </div>
  @endif

  <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
    <a href="{{ route('play.join') }}" class="btn btn-success btn-lg">PLAY AGAIN</a>
    @if(!session('player_account_id'))
      <a href="{{ route('player.register') }}" class="btn btn-outline btn-lg">SAVE STATS</a>
    @else
      <a href="{{ route('player.stats') }}" class="btn btn-outline btn-lg">MY STATS</a>
    @endif
  </div>
</div>

<style>
@keyframes podium-rise { from{opacity:0;transform:translateY(40px)} to{opacity:1;transform:none} }
@keyframes suspense-in { from{opacity:0;transform:scale(.7) translateY(30px)} to{opacity:1;transform:none} }
@keyframes suspense-out { from{opacity:1;transform:scale(1)} to{opacity:0;transform:scale(1.1)} }
@keyframes confetti-fall { 0%{transform:translateY(-20px) rotate(0deg);opacity:1} 100%{transform:translateY(100vh) rotate(720deg);opacity:0} }
</style>
@endsection

@push('scripts')
<script src="/js/sounds.js?v={{ filemtime(public_path('js/sounds.js')) }}"></script>
<script src="/js/confetti.js?v={{ filemtime(public_path('js/confetti.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  QB.Audio.init();

  @php
    $podiumPlayers = [];
    foreach([2,1,0] as $pos) {  // 3rd, 2nd, 1st
      if($players->has($pos)) {
        $p = $players[$pos];
        $podiumPlayers[] = [
          'pos'      => $pos + 1,
          'name'     => $p->nickname,
          'score'    => $p->score,
          'medal'    => ['🥇','🥈','🥉'][$pos],
          'place'    => ['1st Place','2nd Place','3rd Place'][$pos],
          'isYou'    => isset($player) && $p->id === $player->id,
        ];
      }
    }
  @endphp

  const podiumPlayers = @json($podiumPlayers);
  const myRank        = {{ $rank ?? 99 }};
  const overlay       = document.getElementById('suspense-overlay');
  const content       = document.getElementById('suspense-content');

  function showPlayer(idx) {
    if (idx >= podiumPlayers.length) {
      // All done — hide overlay, show results
      overlay.style.animation = 'suspense-out .5s ease forwards';
      setTimeout(function() {
        overlay.style.display = 'none';
        document.getElementById('main-results').style.display = '';
        if (myRank === 1) {
          QB.Audio.sfx.podium();
          QB.Confetti.burst(200);
          setTimeout(function(){ QB.Confetti.burst(150); }, 1500);
        } else {
          QB.Audio.sfx.leaderboard();
        }
      }, 500);
      return;
    }

    const p = podiumPlayers[idx];

    // Animate out previous
    content.style.animation = 'suspense-out .35s ease forwards';

    setTimeout(function() {
      // Update content
      document.getElementById('suspense-place').textContent = p.place;
      document.getElementById('suspense-medal').textContent = p.medal;
      document.getElementById('suspense-name').textContent  = p.name;
      document.getElementById('suspense-score').textContent = Number(p.score).toLocaleString() + ' pts';

      const youEl = document.getElementById('suspense-you');
      if (youEl) youEl.style.display = p.isYou ? 'inline-block' : 'none';

      // Animate in
      content.style.animation = 'suspense-in .6s cubic-bezier(.34,1.4,.64,1) forwards';

      // Sound
      if (idx === 2) QB.Audio.sfx.podium();
      else QB.Audio.sfx.leaderboard();

      // Confetti burst for 1st place
      if (p.pos === 1) {
        QB.Confetti.burst(180);
        setTimeout(function(){ QB.Confetti.burst(120); }, 800);
      }

      // Next player after delay (longer for 1st)
      var delay = p.pos === 1 ? 4000 : 3000;
      setTimeout(function() { showPlayer(idx + 1); }, delay);
    }, 350);
  }

  // Start suspense after 1 second
  setTimeout(function() { showPlayer(0); }, 800);
});
</script>
@endpush
