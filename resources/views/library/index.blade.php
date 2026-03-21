@extends('layouts.app')
@section('title', 'Quiz Library')

@section('content')
<div style="background:var(--qb-purple);padding:2rem 0;border-bottom:3px solid rgba(0,0,0,0.3)">
  <div class="container">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
      <div>
        <h1 style="font-size:1.8rem;text-transform:uppercase;letter-spacing:-0.5px">Quiz Library</h1>
        <p style="color:rgba(255,255,255,0.6);font-size:0.88rem;margin-top:0.2rem">Browse and play public quizzes</p>
      </div>
      <form method="GET" action="{{ route('library') }}" class="library-search-form">
        <input type="text" name="search" class="form-control library-search-input"
               value="{{ request('search') }}" placeholder="Search quizzes..." />
        <div class="library-filter-row">
          <select name="category" class="form-control library-search-select">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
              <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
          </select>
          <button class="btn btn-white btn-sm">SEARCH</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div style="padding:2rem 0">
  <div class="container">
    @if($quizzes->isEmpty())
      <div class="card text-center" style="padding:3rem;border:2px dashed rgba(255,255,255,0.1)">
        <div style="font-size:2.5rem;margin-bottom:1rem">📚</div>
        <h2 style="text-transform:uppercase;font-size:1.2rem;margin-bottom:0.5rem">No Quizzes Found</h2>
        <p class="text-muted">Try a different search or check back later.</p>
      </div>
    @else
      <div class="grid-3">
        @foreach($quizzes as $quiz)
          <a href="{{ route('library.show', $quiz) }}" style="text-decoration:none">
            <div class="quiz-card" style="height:100%">
              @if($quiz->category)
                <div style="font-size:0.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--qb-purple3);margin-bottom:0.4rem">{{ $quiz->category }}</div>
              @endif
              <div class="quiz-card-title">{{ $quiz->title }}</div>
              @if($quiz->description)
                <p class="text-muted" style="font-size:0.82rem;margin-top:0.3rem;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">{{ $quiz->description }}</p>
              @endif
              <div class="quiz-card-meta mt-2">
                <span>{{ $quiz->questions_count }} questions</span>
                <span>by {{ $quiz->user->name }}</span>
                <span>{{ $quiz->created_at->diffForHumans() }}</span>
              </div>
              @if($quiz->tags)
                <div style="margin-top:0.6rem;display:flex;flex-wrap:wrap;gap:0.3rem">
                  @foreach(explode(',', $quiz->tags) as $tag)
                    <span style="font-size:0.7rem;font-weight:700;padding:.15rem .5rem;background:rgba(255,255,255,0.06);border:1px solid var(--qb-border);border-radius:var(--radius);text-transform:uppercase;color:var(--qb-muted)">{{ trim($tag) }}</span>
                  @endforeach
                </div>
              @endif
            </div>
          </a>
        @endforeach
      </div>

      <div style="margin-top:2rem">{{ $quizzes->links() }}</div>
    @endif
  </div>
</div>
@endsection
