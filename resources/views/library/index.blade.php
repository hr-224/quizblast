@extends('layouts.app')
@section('title', 'Quiz Library')

@push('head')
<style>
.library-hero {
  background: #0a0010;
  padding: 2.5rem 0 2rem;
  border-bottom: 1px solid rgba(255,208,0,.15);
  position: relative;
  overflow: hidden;
}
.library-hero::before {
  content: '';
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse 70% 100% at 10% 50%, rgba(100,40,200,.3) 0%, transparent 65%),
    radial-gradient(ellipse 50% 80% at 85% 30%, rgba(60,20,140,.2) 0%, transparent 60%);
  pointer-events: none;
}
.library-hero-inner { position: relative; z-index: 1; }
.library-hero-title {
  font-family: 'Montserrat', sans-serif;
  font-size: clamp(1.6rem, 4vw, 2.2rem);
  font-weight: 900; text-transform: uppercase; letter-spacing: -0.5px;
  background: linear-gradient(135deg, #ffd000 0%, #fff 40%, #c084fc 80%, #a855f7 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0 0 .2rem;
}
.library-search-form {
  display: flex; flex-direction: column; gap: .5rem;
  width: 100%; max-width: 480px;
}
.library-search-input {
  background: rgba(255,255,255,.07) !important;
  border: 1px solid rgba(255,208,0,.2) !important;
  border-radius: 6px !important;
}
.library-search-input:focus {
  border-color: rgba(255,208,0,.5) !important;
  outline: none; box-shadow: 0 0 0 3px rgba(255,208,0,.1) !important;
}
.library-search-select {
  background: rgba(255,255,255,.07) !important;
  border: 1px solid rgba(255,255,255,.12) !important;
  border-radius: 6px !important;
  color: #fff !important;
}
.library-filter-row { display: flex; gap: .5rem; }
.library-filter-row .btn-white {
  background: #ffd000; color: #1a0533;
  font-family: 'Montserrat', sans-serif;
  font-weight: 900; letter-spacing: 1px;
  border: none; white-space: nowrap;
  box-shadow: 0 2px 12px rgba(255,208,0,.35);
}
.library-grid { padding: 2rem 0; background: #0a0010; min-height: 50vh; }
.quiz-card {
  background: rgba(255,255,255,.055) !important;
  border: 1px solid rgba(255,255,255,.1) !important;
  border-radius: 10px !important;
  transition: border-color .18s, transform .15s, box-shadow .18s;
}
.quiz-card:hover {
  border-color: rgba(255,208,0,.35) !important;
  transform: translateY(-2px);
  box-shadow: 0 6px 28px rgba(0,0,0,.4), 0 0 20px rgba(255,208,0,.06);
}
</style>
@endpush

@section('content')

<div class="library-hero">
  <div class="container library-hero-inner">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1.5rem">
      <div>
        <div class="library-hero-title">Quiz Library</div>
        <p style="color:rgba(255,255,255,.5);font-size:.88rem;margin:0">Browse and play public quizzes</p>
      </div>
      <form method="GET" action="{{ route('library') }}" class="library-search-form">
        <input type="text" name="search" class="form-control library-search-input"
               value="{{ request('search') }}" placeholder="Search quizzes…" />
        <div class="library-filter-row">
          <select name="category" class="form-control library-search-select" style="flex:1">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
              <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
          </select>
          <button class="btn btn-white btn-sm library-filter-row">SEARCH</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="library-grid">
  <div class="container">
    @if($quizzes->isEmpty())
      <div class="card text-center" style="padding:3rem;border:2px dashed rgba(255,255,255,.1);background:rgba(255,255,255,.03)">
        <div style="font-size:2.5rem;margin-bottom:1rem">📚</div>
        <h2 style="text-transform:uppercase;font-size:1.2rem;margin-bottom:.5rem">No Quizzes Found</h2>
        <p class="text-muted">Try a different search or check back later.</p>
      </div>
    @else
      <div class="grid-3">
        @foreach($quizzes as $quiz)
          <a href="{{ route('library.show', $quiz) }}" style="text-decoration:none">
            <div class="quiz-card" style="height:100%;padding:1.25rem">
              @if($quiz->category)
                <div style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--qb-yellow);opacity:.8;margin-bottom:.4rem">{{ $quiz->category }}</div>
              @endif
              <div class="quiz-card-title">{{ $quiz->title }}</div>
              @if($quiz->description)
                <p class="text-muted" style="font-size:.82rem;margin-top:.3rem;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">{{ $quiz->description }}</p>
              @endif
              <div class="quiz-card-meta mt-2">
                <span>{{ $quiz->questions_count }} questions</span>
                <span>by {{ $quiz->user->name }}</span>
                <span>{{ $quiz->created_at->diffForHumans() }}</span>
              </div>
              @if($quiz->tags)
                <div style="margin-top:.6rem;display:flex;flex-wrap:wrap;gap:.3rem">
                  @foreach(explode(',', $quiz->tags) as $tag)
                    <span style="font-size:.7rem;font-weight:700;padding:.15rem .5rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:var(--radius);text-transform:uppercase;color:var(--qb-muted)">{{ trim($tag) }}</span>
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
