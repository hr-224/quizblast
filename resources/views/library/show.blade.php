@extends('layouts.app')
@section('title', $quiz->title)
@section('content')
<div style="padding:2.5rem 0">
  <div class="container-md">
    <a href="{{ route('library') }}" class="text-muted" style="font-size:.88rem">← Library</a>
    <div style="margin:1.5rem 0;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem">
      <div>
        @if($quiz->category)
          <div style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--qb-purple3);margin-bottom:.4rem">{{ $quiz->category }}</div>
        @endif
        <h1 style="font-size:2rem;text-transform:uppercase;letter-spacing:-.5px">{{ $quiz->title }}</h1>
        @if($quiz->description)
          <p class="text-muted mt-1">{{ $quiz->description }}</p>
        @endif
        <div style="display:flex;gap:1rem;margin-top:.75rem;flex-wrap:wrap">
          <span class="stat-chip">{{ $quiz->questions->count() }} questions</span>
          <span class="stat-chip">by <span class="val">{{ $quiz->user->name }}</span></span>
          <span class="stat-chip">{{ $quiz->created_at->diffForHumans() }}</span>
        </div>
      </div>
      @auth
        <a href="{{ route('game.start', $quiz) }}" class="btn btn-success btn-lg">▶ HOST THIS QUIZ</a>
      @else
        <a href="{{ route('register') }}" class="btn btn-success btn-lg">HOST THIS QUIZ</a>
      @endauth
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">{{ $quiz->questions->count() }} Questions</div></div>
      @foreach($quiz->questions as $idx => $question)
        @php $borderStyle = $loop->last ? '' : 'border-bottom:1px solid var(--qb-border);'; @endphp
        <div style="padding:.9rem 0;{{ $borderStyle }}">
          <div style="display:flex;align-items:flex-start;gap:1rem">
            <div style="background:var(--qb-purple3);font-family:'Montserrat',sans-serif;font-weight:900;font-size:.8rem;padding:.3rem .6rem;border-radius:var(--radius);flex-shrink:0">{{ $idx+1 }}</div>
            <div style="flex:1">
              <div style="font-weight:700;font-size:.92rem;margin-bottom:.5rem">{{ $question->question_text }}</div>
              <div style="display:flex;flex-wrap:wrap;gap:.35rem">
                @foreach($question->answers as $ans)
                  <span style="padding:.2rem .6rem;border-radius:var(--radius);font-size:.78rem;font-weight:700;background:{{ $ans->is_correct ? 'rgba(38,137,12,.2)' : 'rgba(255,255,255,.05)' }};border:1px solid {{ $ans->is_correct ? 'rgba(38,137,12,.4)' : 'var(--qb-border)' }};color:{{ $ans->is_correct ? '#5ddd3a' : 'var(--qb-muted)' }}">
                    {{ $ans->is_correct ? '✓ ' : '' }}{{ $ans->answer_text }}
                  </span>
                @endforeach
              </div>
            </div>
            <div style="font-size:.75rem;color:var(--qb-muted);flex-shrink:0;text-align:right">{{ $question->time_limit }}s<br>{{ $question->points }}pts</div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</div>
@endsection
