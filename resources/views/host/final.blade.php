@extends('layouts.game')
@section('title', 'Final Results')

@section('topbar-center')
  <span class="topbar-title">🏆 Final results</span>
@endsection

@section('topbar-right')
  <div class="host-actions">
    <a href="{{ route('dashboard.history', $game) }}" class="btn btn-outline btn-sm">📊 History</a>
    <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm">← Dashboard</a>
  </div>
@endsection

@section('content')

{{-- Suspense reveal --}}
<div id="suspense-overlay" class="suspense-overlay is-host">
  <div class="suspense-glow"></div>
  <div id="suspense-content" class="suspense-content">
    <div id="suspense-place" class="suspense-place"></div>
    <div id="suspense-medal" class="suspense-medal"></div>
    <div id="suspense-name" class="suspense-name"></div>
    <div id="suspense-score" class="suspense-score"></div>
  </div>
</div>

{{-- Main results (hidden until the reveal is done) --}}
<div id="main-results" class="host-final hidden">

  <div class="trophy" aria-hidden="true">🏆</div>
  <h1 class="host-final-title">Game over!</h1>
  <p class="text-muted">{{ $game->quiz->title }}</p>

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
            <div class="podium-name">{{ $p->nickname }}</div>
            <div class="podium-score">{{ number_format($p->score) }}</div>
            @if($p->best_streak >= 3)
              <div class="podium-streak">🔥{{ $p->best_streak }} streak</div>
            @endif
            <div class="podium-block">{{ $podiumMedals[$pi] }}</div>
          </div>
        @endif
      @endforeach
    </div>
  @endif

  {{-- Full leaderboard --}}
  @if($players->isNotEmpty())
    <div class="card mb-3">
      <div class="card-header"><div class="card-title">Full leaderboard</div></div>
      <ul class="leaderboard-list">
        @foreach($players as $idx => $player)
          <li class="leaderboard-item{{ $idx < 3 ? ' lb-rank-' . ($idx + 1) : '' }}">
            <div class="leaderboard-rank">@if($idx == 0) 🥇 @elseif($idx == 1) 🥈 @elseif($idx == 2) 🥉 @else {{ $idx + 1 }} @endif</div>
            <div class="leaderboard-name">
              {{ $player->nickname }}
              @if($player->best_streak >= 3)<span class="lb-streak-badge">🔥{{ $player->best_streak }}</span>@endif
            </div>
            <div class="leaderboard-score">{{ number_format($player->score) }}</div>
          </li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="host-final-actions">
    <a href="{{ route('game.start', $game->quiz) }}" class="btn btn-success btn-lg">▶ Play again</a>
    <a href="{{ route('dashboard.history', $game) }}" class="btn btn-outline btn-lg">📊 Detailed stats</a>
    <a href="{{ route('quizzes.edit', $game->quiz) }}" class="btn btn-outline btn-lg">Edit quiz</a>
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
    $podiumData = [];
    // Reveal order: 3rd, 2nd, 1st (only positions that exist)
    foreach ([2, 1, 0] as $pos) {
      if ($players->has($pos)) {
        $p = $players[$pos];
        $podiumData[] = [
          'pos'   => $pos + 1,
          'name'  => $p->nickname,
          'score' => $p->score,
          'medal' => ['🥇', '🥈', '🥉'][$pos],
          'place' => ['1st place', '2nd place', '3rd place'][$pos],
        ];
      }
    }
  @endphp

  const podium  = @json($podiumData);
  const overlay = document.getElementById('suspense-overlay');
  const content = document.getElementById('suspense-content');

  function burst(n) {
    if (window.QB && QB.UI && QB.UI.fx() === 'party') QB.Confetti.burst(n);
  }

  function showPlayer(idx) {
    if (idx >= podium.length) {
      // Reveal finished — fade the overlay, show the results
      overlay.style.transition = 'opacity .6s ease';
      overlay.style.opacity = '0';
      setTimeout(function () {
        overlay.classList.add('hidden');
        document.getElementById('main-results').classList.remove('hidden');
        burst(220);
        QB.Audio.sfx.podium();
        setTimeout(function () { burst(150); }, 1200);
        setTimeout(function () { burst(100); }, 2400);
      }, 600);
      return;
    }

    const p = podium[idx];
    content.style.animation = 'suspense-out .35s ease forwards';

    setTimeout(function () {
      document.getElementById('suspense-place').textContent = p.place;
      document.getElementById('suspense-medal').textContent = p.medal;
      document.getElementById('suspense-name').textContent  = p.name;
      document.getElementById('suspense-score').textContent = Number(p.score).toLocaleString() + ' pts';

      content.style.animation = 'suspense-in .6s cubic-bezier(.34,1.4,.64,1) forwards';

      if (p.pos === 1) {
        burst(200);
        setTimeout(function () { burst(120); }, 700);
      } else {
        QB.Audio.sfx.leaderboard();
      }

      // Longer pause on first place
      setTimeout(function () { showPlayer(idx + 1); }, p.pos === 1 ? 4500 : 3000);
    }, 380);
  }

  setTimeout(function () { showPlayer(0); }, 1000);
});
</script>
@endpush
