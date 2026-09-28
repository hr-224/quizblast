@extends('layouts.app', ['fxBg' => true])
@section('title', 'Quiz Library')

@php
  $sorts = ['new' => 'Newest', 'popular' => 'Most played', 'questions' => 'Most questions'];
  $link  = fn (array $over) => route('library', array_filter(
      array_merge(['search' => $search, 'category' => $category, 'sort' => $sort === 'new' ? null : $sort], $over),
      fn ($v) => $v !== null && $v !== ''
  ));
@endphp

@section('content')
<div class="library-hero">
  <div class="container">
    <div class="page-head">
      <div>
        <div class="library-hero-title">Quiz library</div>
        <p class="text-muted">Pick a public quiz to play or host</p>
      </div>
      <form method="GET" action="{{ route('library') }}" class="library-search-form">
        @if($sort !== 'new')<input type="hidden" name="sort" value="{{ $sort }}" />@endif
        <input type="text" name="search" class="form-control library-search-input" value="{{ $search }}" placeholder="Search quizzes…" aria-label="Search quizzes" />
        <div class="library-filter-row">
          <select name="category" class="form-control library-search-select" aria-label="Category">
            <option value="">All categories</option>
            @foreach($categories as $name => $total)
              <option value="{{ $name }}" {{ $category === $name ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
          </select>
          <button type="submit" class="btn btn-white btn-sm">Search</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="lib-bar">
  <div class="container lib-bar-inner">
    <nav class="lib-cats" aria-label="Categories">
      <a href="{{ $link(['category' => null]) }}" class="lib-chip{{ $category === '' ? ' is-active' : '' }}"{!! $category === '' ? ' aria-current="page"' : '' !!}>All</a>
      @foreach($categories as $name => $total)
        <a href="{{ $link(['category' => $name]) }}" class="lib-chip{{ $category === $name ? ' is-active' : '' }}"{!! $category === $name ? ' aria-current="page"' : '' !!}>{{ $name }} <span class="lib-chip-count">{{ $total }}</span></a>
      @endforeach
    </nav>
    <nav class="lib-sort" aria-label="Sort">
      @foreach($sorts as $key => $label)
        <a href="{{ $link(['sort' => $key === 'new' ? null : $key]) }}" class="lib-sort-link{{ $sort === $key ? ' is-active' : '' }}"{!! $sort === $key ? ' aria-current="true"' : '' !!}>{{ $label }}</a>
      @endforeach
    </nav>
  </div>
</div>

<div class="library-grid">
  <div class="container">
    @if($browse && $featured->isNotEmpty())
      <section class="lib-featured" aria-labelledby="lib-featured-h">
        <div class="lib-row-head">
          <h2 class="lib-section-title" id="lib-featured-h">{{ $featuredLabel }}</h2>
        </div>
        <div class="grid-3">
          @foreach($featured as $quiz)
            <x-quiz-card :quiz="$quiz" :featured="true" />
          @endforeach
        </div>
      </section>
    @endif

    @if($browse && $rows->isNotEmpty())
      @foreach($rows as $row)
        <section class="lib-row" aria-labelledby="lib-row-{{ $loop->index }}">
          <div class="lib-row-head">
            <h2 class="lib-section-title" id="lib-row-{{ $loop->index }}">{{ $row['name'] }}</h2>
            <a href="{{ $link(['category' => $row['name']]) }}" class="lib-see-all">See all {{ $row['total'] }}</a>
          </div>
          <div class="grid-3">
            @foreach($row['quizzes'] as $quiz)
              <x-quiz-card :quiz="$quiz" />
            @endforeach
          </div>
        </section>
      @endforeach
    @endif

    <section class="lib-results" aria-labelledby="lib-results-h">
      <div class="lib-row-head">
        <h2 class="lib-section-title" id="lib-results-h">
          @if($search !== '')Results for “{{ $search }}”@elseif($category !== ''){{ $category }}@else All quizzes @endif
        </h2>
        <span class="text-muted">{{ number_format($quizzes->total()) }} {{ Str::plural('quiz', $quizzes->total()) }}</span>
      </div>
      @if($quizzes->isEmpty())
        <div class="card q-empty text-center">
          <div class="state-emoji" aria-hidden="true">📚</div>
          <h2>No quizzes found</h2>
          <p class="text-muted">Try a different search or category, or check back later.</p>
        </div>
      @else
        <div class="grid-3">
          @foreach($quizzes as $quiz)
            <x-quiz-card :quiz="$quiz" />
          @endforeach
        </div>
        <div class="mt-3">{{ $quizzes->withQueryString()->links('pagination::default') }}</div>
      @endif
    </section>
  </div>
</div>
@endsection
