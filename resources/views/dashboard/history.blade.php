@extends('layouts.app')
@section('title', 'Game History')

@section('content')
<div class="container mt-4">
  <a href="{{ route('dashboard') }}" class="text-muted">← Dashboard</a>

  <div class="mt-2 mb-3">
    <h1>Game history</h1>
    <p class="text-muted mt-1">{{ $game->quiz->title }} · PIN {{ $game->pin }} · {{ $game->created_at->format('M d, Y g:ia') }}</p>
  </div>

  <div class="grid-4 mb-3 history-stats">
    <div class="card text-center history-stat">
      <div class="field-hint">Players</div>
      <div class="q-preview-num-big">{{ $players->count() }}</div>
    </div>
    <div class="card text-center history-stat">
      <div class="field-hint">Questions</div>
      <div class="q-preview-num-big">{{ count($breakdown) }}</div>
    </div>
    <div class="card text-center history-stat">
      <div class="field-hint">Top score</div>
      <div class="q-preview-num-big is-amber">{{ number_format($players->max('score')) }}</div>
    </div>
    <div class="card text-center history-stat">
      <div class="field-hint">Avg score</div>
      <div class="q-preview-num-big">{{ number_format($players->avg('score')) }}</div>
    </div>
  </div>

  <h2 class="card-title mb-2">Question breakdown</h2>
  @foreach($breakdown as $idx => $item)
    <div class="card mb-2">
      <div class="page-head page-head-tight">
        <div>
          <div class="field-hint">Q{{ $idx + 1 }}</div>
          <div class="q-card-text">{{ $item['question']->question_text }}</div>
          <div class="q-card-chips mt-2">
            @foreach($item['question']->answers as $ans)
              <x-answer-chip :text="$ans->answer_text" :correct="$ans->is_correct" />
            @endforeach
          </div>
        </div>
        <div class="history-q-score">
          <div class="q-preview-num-big {{ $item['correct_count'] / max($item['total_players'], 1) >= 0.6 ? 'is-ok' : ($item['correct_count'] / max($item['total_players'], 1) >= 0.3 ? 'is-amber' : 'is-bad') }}">
            {{ $item['correct_count'] }}/{{ $item['total_players'] }}
          </div>
          <div class="field-hint">got it right</div>
        </div>
      </div>

      <div class="q-card-chips mt-2">
        @foreach($players as $player)
          @php $pa = $item['player_answers'][$player->id] ?? null; @endphp
          <span class="player-answer-pill{{ $pa && $pa['correct'] ? ' is-correct' : '' }}">
            {{ $pa && $pa['correct'] ? '✓' : '✗' }} {{ $player->nickname }}
            @if($pa && $pa['time_ms'])<span class="field-hint">{{ round($pa['time_ms'] / 1000, 1) }}s</span>@endif
            @if($pa && $pa['power_up'])<span class="text-yellow">⚡</span>@endif
          </span>
        @endforeach
      </div>
    </div>
  @endforeach

  <h2 class="card-title mt-4 mb-2">Final standings</h2>
  <div class="card">
    <ul class="leaderboard-list">
      @foreach($players->sortByDesc('score') as $idx => $player)
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
</div>
@endsection
