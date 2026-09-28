@extends('layouts.app', ['fxBg' => true])
@section('title', $quiz->title)

@section('content')
<div class="container-md mt-4">
  <a href="{{ route('library') }}" class="text-muted">← Library</a>
  @if($quiz->banner_url)
    <div class="lib-banner"><img class="lib-banner-img" src="{{ $quiz->banner_url }}" referrerpolicy="no-referrer" alt="" /></div>
  @endif
  <div class="page-head">
    <div>
      @if($quiz->category)<div class="quiz-tile-tag">{{ $quiz->category }}</div>@endif
      <h1>{{ $quiz->title }}</h1>
      @if($quiz->description)<p class="text-muted mt-1">{{ $quiz->description }}</p>@endif
      <div class="q-card-chips mt-2">
        <span class="stat-chip">{{ $quiz->questions->count() }} questions</span>
        <span class="stat-chip">by <span class="val">{{ $quiz->user->name }}</span></span>
        <span class="stat-chip">{{ $quiz->created_at->diffForHumans() }}</span>
      </div>
    </div>
    @auth
      <a href="{{ route('game.start', $quiz) }}" class="btn btn-success btn-lg">▶ Host this quiz</a>
    @else
      <a href="{{ route('register') }}" class="btn btn-success btn-lg">Host this quiz</a>
    @endauth
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">{{ $quiz->questions->count() }} questions</div></div>
    @foreach($quiz->questions as $idx => $question)
      <div class="q-preview-row{{ $loop->last ? '' : ' q-preview-row-divider' }}">
        <div class="q-preview-num">{{ $idx + 1 }}</div>
        <div class="q-preview-body">
          <div class="q-card-text">{{ $question->question_text }}</div>
          <div class="q-card-chips">
            @foreach($question->answers as $ans)
              <x-answer-chip :text="$ans->answer_text" />
            @endforeach
          </div>
        </div>
        <div class="q-preview-meta">{{ $question->time_limit }}s<br>{{ $question->points }}pts</div>
      </div>
    @endforeach
  </div>
</div>
@endsection
