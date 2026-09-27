@extends('layouts.app')
@section('title', 'Quiz Library')

@section('content')
<div class="library-hero">
  <div class="container">
    <div class="page-head">
      <div>
        <div class="library-hero-title">Quiz library</div>
        <p class="text-muted">Browse and play public quizzes</p>
      </div>
      <form method="GET" action="{{ route('library') }}" class="library-search-form">
        <input type="text" name="search" class="form-control library-search-input" value="{{ request('search') }}" placeholder="Search quizzes…" />
        <div class="library-filter-row">
          <select name="category" class="form-control library-search-select">
            <option value="">All categories</option>
            @foreach($categories as $cat)
              <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
          </select>
          <button type="submit" class="btn btn-white btn-sm">Search</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="library-grid">
  <div class="container">
    @if($quizzes->isEmpty())
      <div class="card q-empty text-center">
        <div class="state-emoji" aria-hidden="true">📚</div>
        <h2>No quizzes found</h2>
        <p class="text-muted">Try a different search or check back later.</p>
      </div>
    @else
      <div class="grid-3">
        @foreach($quizzes as $quiz)
          <a href="{{ route('library.show', $quiz) }}" class="quiz-card-link">
            <div class="quiz-card">
              @if($quiz->category)<div class="quiz-tile-tag">{{ $quiz->category }}</div>@endif
              <div class="quiz-card-title">{{ $quiz->title }}</div>
              @if($quiz->description)<p class="text-muted quiz-card-desc">{{ $quiz->description }}</p>@endif
              <div class="quiz-card-meta mt-2">
                <span>{{ $quiz->questions_count }} questions</span>
                <span>by {{ $quiz->user->name }}</span>
                <span>{{ $quiz->created_at->diffForHumans() }}</span>
              </div>
              @if($quiz->tags)
                <div class="q-card-chips mt-2">
                  @foreach(explode(',', $quiz->tags) as $tag)
                    <span class="ans-chip">{{ trim($tag) }}</span>
                  @endforeach
                </div>
              @endif
            </div>
          </a>
        @endforeach
      </div>
      <div class="mt-3">{{ $quizzes->links() }}</div>
    @endif
  </div>
</div>
@endsection
