@extends('layouts.game')
@section('title', 'Results')

@section('topbar-center')
  <span class="topbar-title">🏆 Final results</span>
@endsection

@section('topbar-right')
  <a href="{{ route('play.join') }}" class="btn btn-outline btn-sm">Play again</a>
@endsection

@section('content')

{{-- Suspense reveal --}}
<div id="suspense-overlay" class="suspense-overlay">
  <div class="suspense-glow"></div>
  <div id="suspense-content" class="suspense-content">
    <div id="suspense-place" class="suspense-place"></div>
    <div id="suspense-medal" class="suspense-medal"></div>
    <div id="suspense-name" class="suspense-name"></div>
    <div id="suspense-score" class="suspense-score"></div>
    @if($rank !== null && $rank <= 3)
      <div id="suspense-you" class="suspense-you hidden">That's you! 🎉</div>
    @endif
  </div>
</div>

{{-- Main results (hidden until the reveal is done) --}}
<div id="main-results" class="results hidden">

  @if($player)
    <div class="result-hero">
      <div class="result-medal {{ $rank !== null && $rank <= 3 ? 'is-podium' : '' }}">
        @if($rank == 1) 🥇 @elseif($rank == 2) 🥈 @elseif($rank == 3) 🥉 @else 🎮 @endif
      </div>
      <h1 class="result-name">{{ $player->nickname }}</h1>
      <p class="result-rank">{{ $rank !== null ? '#' . $rank . ' of ' . $players->count() . ' players' : 'Spectating' }}</p>
      <div class="result-stats">
        <div class="card result-stat">
          <div class="result-stat-label">Score</div>
          <div class="result-stat-val is-score">{{ number_format($player->score) }}</div>
        </div>
        <div class="card result-stat">
          <div class="result-stat-label">Best streak</div>
          <div class="result-stat-val is-streak">🔥{{ $player->best_streak }}</div>
        </div>
        @if($personalStats)
          <div class="card result-stat">
            <div class="result-stat-label">Accuracy</div>
            <div class="result-stat-val is-accuracy">{{ $personalStats['accuracy'] }}%</div>
          </div>
        @endif
      </div>
    </div>
  @endif

  {{-- Podium --}}
  @if($players->count() >= 1)
    @php
      $podiumOrder  = [1, 0, 2];
      $podiumMedals = ['🥈', '🥇', '🥉'];
    @endphp
    <div class="podium">
      @foreach($podiumOrder as $pi => $pos)
        @if($players->has($pos))
          @php $p = $players[$pos]; @endphp
          <div class="podium-col podium-{{ $pos + 1 }}">
            <div class="podium-name {{ (isset($player) && $p->id === $player->id) ? 'is-you' : '' }}">{{ $p->nickname }}</div>
            <div class="podium-score">{{ number_format($p->score) }}</div>
            <div class="podium-block">{{ $podiumMedals[$pi] }}</div>
          </div>
        @endif
      @endforeach
    </div>
  @endif

  {{-- Leaderboard --}}
  <div class="card mb-3">
    <div class="card-header"><div class="card-title">Leaderboard</div></div>
    <ul class="leaderboard-list">
      @foreach($players as $idx => $p)
        @php $isMe = isset($player) && $p->id === $player->id; @endphp
        <li class="leaderboard-item{{ $idx < 3 ? ' lb-rank-' . ($idx + 1) : '' }}{{ $isMe ? ' lb-me' : '' }}">
          <div class="leaderboard-rank">@if($idx == 0) 🥇 @elseif($idx == 1) 🥈 @elseif($idx == 2) 🥉 @else {{ $idx + 1 }} @endif</div>
          <div class="leaderboard-name">
            {{ $p->nickname }}
            @if($p->best_streak >= 3)<span class="lb-streak-badge">🔥{{ $p->best_streak }}</span>@endif
            @if($isMe)<span class="lb-you-badge">you</span>@endif
          </div>
          <div class="leaderboard-score">{{ number_format($p->score) }}</div>
        </li>
      @endforeach
    </ul>
  </div>

  @if($personalStats)
    <div class="card mb-3">
      <div class="card-header"><div class="card-title">Your performance</div></div>
      <div class="perf-summary">
        <span class="stat-chip">Correct <span class="val">{{ $personalStats['correct'] }}/{{ $personalStats['total'] }}</span></span>
        <span class="stat-chip">Accuracy <span class="val">{{ $personalStats['accuracy'] }}%</span></span>
        <span class="stat-chip">Best streak <span class="val">🔥{{ $personalStats['best_streak'] }}</span></span>
      </div>
      @foreach($personalStats['questions'] as $idx => $stat)
        <div class="perf-row">
          <div class="perf-mark {{ $stat['correct'] ? 'is-right' : 'is-wrong' }}">{{ $stat['correct'] ? '✓' : '✗' }}</div>
          <div class="perf-q">Q{{ $idx + 1 }}: {{ $stat['question'] }}</div>
          <div class="perf-pts {{ $stat['correct'] ? 'is-right' : 'is-wrong' }}">+{{ number_format($stat['points']) }}</div>
          @if($stat['time_ms'])
            <div class="perf-time">{{ round($stat['time_ms'] / 1000, 1) }}s</div>
          @endif
        </div>
      @endforeach
    </div>
  @endif

  <div class="result-actions">
    <a href="{{ route('play.join') }}" class="btn btn-success btn-lg">Play again</a>
    @if(!session('player_account_id'))
      <a href="{{ route('player.register') }}" class="btn btn-outline btn-lg">Save stats</a>
    @else
      <a href="{{ route('player.stats') }}" class="btn btn-outline btn-lg">My stats</a>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script src="/js/sounds.js?v={{ filemtime(public_path('js/sounds.js')) }}"></script>
<script src="/js/confetti.js?v={{ filemtime(public_path('js/confetti.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  QB.Audio.init();

  @php
    $podiumPlayers = [];
    foreach ([2, 1, 0] as $pos) {  // 3rd, 2nd, 1st
      if ($players->has($pos)) {
        $p = $players[$pos];
        $podiumPlayers[] = [
          'pos'   => $pos + 1,
          'name'  => $p->nickname,
          'score' => $p->score,
          'medal' => ['🥇', '🥈', '🥉'][$pos],
          'place' => ['1st place', '2nd place', '3rd place'][$pos],
          'isYou' => isset($player) && $p->id === $player->id,
        ];
      }
    }
  @endphp

  const podiumPlayers = @json($podiumPlayers);
  const myRank        = {{ $rank ?? 99 }};
  const overlay       = document.getElementById('suspense-overlay');
  const content       = document.getElementById('suspense-content');

  function burst(n) {
    if (window.QB && QB.UI && QB.UI.fx() === 'party') QB.Confetti.burst(n);
  }

  function showPlayer(idx) {
    if (idx >= podiumPlayers.length) {
      // Reveal finished — fade the overlay, show the results
      overlay.style.animation = 'suspense-out .5s ease forwards';
      setTimeout(function () {
        overlay.classList.add('hidden');
        document.getElementById('main-results').classList.remove('hidden');
        if (myRank === 1) {
          QB.Audio.sfx.podium();
          burst(200);
          setTimeout(function () { burst(150); }, 1500);
        } else {
          QB.Audio.sfx.leaderboard();
        }
      }, 500);
      return;
    }

    const p = podiumPlayers[idx];
    content.style.animation = 'suspense-out .35s ease forwards';

    setTimeout(function () {
      document.getElementById('suspense-place').textContent = p.place;
      document.getElementById('suspense-medal').textContent = p.medal;
      document.getElementById('suspense-name').textContent  = p.name;
      document.getElementById('suspense-score').textContent = Number(p.score).toLocaleString() + ' pts';

      const youEl = document.getElementById('suspense-you');
      if (youEl) youEl.classList.toggle('hidden', !p.isYou);

      content.style.animation = 'suspense-in .6s cubic-bezier(.34,1.4,.64,1) forwards';

      if (p.pos === 1) QB.Audio.sfx.podium();
      else QB.Audio.sfx.leaderboard();

      if (p.pos === 1) {
        burst(180);
        setTimeout(function () { burst(120); }, 800);
      }

      // Longer pause on first place
      setTimeout(function () { showPlayer(idx + 1); }, p.pos === 1 ? 4000 : 3000);
    }, 350);
  }

  setTimeout(function () { showPlayer(0); }, 800);
});
</script>
@endpush
