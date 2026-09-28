@props(['quiz', 'featured' => false])
@php $tone = crc32($quiz->category ?: $quiz->title) % 4; @endphp
<article class="quiz-card lib-card{{ $featured ? ' lib-card-featured' : '' }}">
  <div class="lib-cover lib-cover-{{ $tone }}" aria-hidden="true">
    <x-answer-shape :index="$tone" class="lib-cover-shape" />
  </div>
  <div class="lib-card-body">
    @if($quiz->category)<div class="quiz-tile-tag">{{ $quiz->category }}</div>@endif
    <h3 class="quiz-card-title"><a class="lib-card-link" href="{{ route('library.show', $quiz) }}">{{ $quiz->title }}</a></h3>
    @if($quiz->description)<p class="text-muted quiz-card-desc">{{ $quiz->description }}</p>@endif
    <div class="lib-stats">
      <span>{{ $quiz->questions_count }} {{ Str::plural('question', $quiz->questions_count) }}</span>
      @if($quiz->plays_count > 0)<span>{{ number_format($quiz->plays_count) }} {{ Str::plural('play', $quiz->plays_count) }}</span>@endif
      <span>by {{ $quiz->user->name }}</span>
    </div>
  </div>
  <div class="lib-card-actions">
    <a href="{{ route('library.show', $quiz) }}" class="btn btn-outline btn-sm">Preview</a>
    @auth
      <a href="{{ route('game.start', $quiz) }}" class="btn btn-success btn-sm">▶ Host this quiz</a>
    @else
      <a href="{{ route('register') }}" class="btn btn-success btn-sm">Host this quiz</a>
    @endauth
  </div>
</article>
