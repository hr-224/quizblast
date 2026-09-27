@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="dashboard-hero">
  <div class="container page-head">
    <div>
      <h1>Your quizzes</h1>
      <p class="text-muted">Create, manage, and host live quiz games</p>
    </div>
    <a href="{{ route('quizzes.create') }}" class="btn btn-white btn-lg">+ New quiz</a>
  </div>
</div>

<div class="container mt-3">
  @if($quizzes->isEmpty())
    <div class="card q-empty text-center">
      <div class="state-emoji" aria-hidden="true">🎯</div>
      <h2>No quizzes yet</h2>
      <p class="text-muted mb-3">Create your first quiz and start hosting live games</p>
      <a href="{{ route('quizzes.create') }}" class="btn btn-success btn-xl">Create a quiz</a>
    </div>
  @else
    <div class="grid-3">
      @foreach($quizzes as $quiz)
        <div class="quiz-card">
          <div class="page-head quiz-card-head">
            <div class="quiz-card-title">{{ $quiz->title }}</div>
            @if($quiz->is_public)<span class="quiz-card-badge">Public</span>@endif
          </div>
          @if($quiz->description)<p class="text-muted quiz-card-desc">{{ $quiz->description }}</p>@endif
          <div class="quiz-card-meta">
            <span>{{ $quiz->questions_count }} question{{ $quiz->questions_count != 1 ? 's' : '' }}</span>
            <span>{{ $quiz->created_at->diffForHumans() }}</span>
          </div>
          <div class="quiz-card-actions">
            <a href="{{ route('game.start', $quiz) }}" class="btn btn-success btn-sm">▶ Host</a>
            <a href="{{ route('quizzes.edit', $quiz) }}" class="btn btn-outline btn-sm">Edit</a>
            <form method="POST" action="{{ route('quizzes.destroy', $quiz) }}" class="inline-form" data-confirm="Delete this quiz? This cannot be undone.">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-danger btn-sm" aria-label="Delete quiz">✕</button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  @if($quizzes->hasPages())
    <div class="mt-3 d-flex justify-center">{{ $quizzes->withQueryString()->links('pagination::default') }}</div>
  @endif

  @if($recentGames->isNotEmpty())
    <div class="mt-4">
      <h2 class="card-title mb-2">Recent games</h2>
      <div class="card card-flush">
        @foreach($recentGames as $game)
          <div class="q-preview-row q-preview-row-card{{ $loop->last ? '' : ' q-preview-row-divider' }}">
            <div class="q-preview-body">
              <span class="q-card-text">{{ $game->quiz->title ?? 'Deleted quiz' }}</span>
              <span class="text-muted">PIN {{ $game->pin }}</span>
            </div>
            <div class="q-card-chips">
              <span class="stat-chip"><span class="val">{{ strtoupper($game->status) }}</span></span>
              <span class="text-muted">{{ $game->created_at->diffForHumans() }}</span>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endif
</div>
@endsection
