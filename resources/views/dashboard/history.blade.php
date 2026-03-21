@extends('layouts.app')
@section('title', 'Game History')

@section('content')
<div style="padding:2.5rem 0">
  <div class="container">
    <a href="{{ route('dashboard') }}" class="text-muted" style="font-size:0.88rem">← Dashboard</a>

    <div style="margin:1.25rem 0">
      <h1 style="font-size:1.8rem;text-transform:uppercase;letter-spacing:-0.5px">Game History</h1>
      <p class="text-muted mt-1">{{ $game->quiz->title }} · PIN {{ $game->pin }} · {{ $game->created_at->format('M d, Y g:ia') }}</p>
    </div>

    {{-- Summary stats --}}
    <div class="grid-4 mb-3">
      <div class="card text-center" style="padding:1.1rem">
        <div style="font-size:0.7rem;color:var(--qb-muted);text-transform:uppercase;margin-bottom:.25rem">Players</div>
        <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1.8rem">{{ $players->count() }}</div>
      </div>
      <div class="card text-center" style="padding:1.1rem">
        <div style="font-size:0.7rem;color:var(--qb-muted);text-transform:uppercase;margin-bottom:.25rem">Questions</div>
        <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1.8rem">{{ count($breakdown) }}</div>
      </div>
      <div class="card text-center" style="padding:1.1rem">
        <div style="font-size:0.7rem;color:var(--qb-muted);text-transform:uppercase;margin-bottom:.25rem">Top Score</div>
        <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1.8rem;color:var(--qb-yellow)">{{ number_format($players->max('score')) }}</div>
      </div>
      <div class="card text-center" style="padding:1.1rem">
        <div style="font-size:0.7rem;color:var(--qb-muted);text-transform:uppercase;margin-bottom:.25rem">Avg Score</div>
        <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1.8rem">{{ number_format($players->avg('score')) }}</div>
      </div>
    </div>

    {{-- Per-question breakdown --}}
    <h2 style="font-size:0.85rem;font-weight:800;text-transform:uppercase;letter-spacing:.8px;color:var(--qb-muted);margin-bottom:.75rem">Question Breakdown</h2>
    @foreach($breakdown as $idx => $item)
      <div class="card mb-2">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap">
          <div style="flex:1">
            <div style="font-size:0.72rem;color:var(--qb-muted);text-transform:uppercase;margin-bottom:.25rem">Q{{ $idx+1 }}</div>
            <div style="font-weight:700;font-size:0.92rem;margin-bottom:.6rem">{{ $item['question']->question_text }}</div>
            <div style="display:flex;flex-wrap:wrap;gap:0.35rem">
              @foreach($item['question']->answers as $ans)
                <span style="padding:.2rem .6rem;border-radius:var(--radius);font-size:0.75rem;font-weight:700;background:{{ $ans->is_correct ? 'rgba(38,137,12,.2)' : 'rgba(255,255,255,.05)' }};border:1px solid {{ $ans->is_correct ? 'rgba(38,137,12,.4)' : 'var(--qb-border)' }};color:{{ $ans->is_correct ? '#5ddd3a' : 'var(--qb-muted)' }}">
                  {{ $ans->is_correct ? '✓ ' : '' }}{{ $ans->answer_text }}
                </span>
              @endforeach
            </div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1.5rem;color:{{ $item['correct_count'] / max($item['total_players'],1) >= 0.6 ? '#5ddd3a' : ($item['correct_count'] / max($item['total_players'],1) >= 0.3 ? 'var(--qb-yellow)' : 'var(--qb-red)') }}">
              {{ $item['correct_count'] }}/{{ $item['total_players'] }}
            </div>
            <div style="font-size:0.75rem;color:var(--qb-muted)">got it right</div>
          </div>
        </div>

        {{-- Player answers for this question --}}
        <div style="margin-top:.75rem;display:flex;flex-wrap:wrap;gap:.35rem">
          @foreach($players as $player)
            @php $pa = $item['player_answers'][$player->id] ?? null; @endphp
            <div style="padding:.25rem .65rem;border-radius:var(--radius);font-size:0.75rem;font-weight:700;background:{{ $pa && $pa['correct'] ? 'rgba(38,137,12,.15)' : 'rgba(226,27,60,.1)' }};border:1px solid {{ $pa && $pa['correct'] ? 'rgba(38,137,12,.35)' : 'rgba(226,27,60,.25)' }};color:{{ $pa && $pa['correct'] ? '#5ddd3a' : 'var(--qb-muted)' }}">
              {{ $pa && $pa['correct'] ? '✓' : '✗' }} {{ $player->nickname }}
              @if($pa && $pa['time_ms'])<span style="opacity:.6"> {{ round($pa['time_ms']/1000,1) }}s</span>@endif
              @if($pa && $pa['power_up'])<span style="color:var(--qb-yellow)"> ⚡</span>@endif
            </div>
          @endforeach
        </div>
      </div>
    @endforeach

    {{-- Final leaderboard --}}
    <h2 style="font-size:0.85rem;font-weight:800;text-transform:uppercase;letter-spacing:.8px;color:var(--qb-muted);margin-top:2rem;margin-bottom:.75rem">Final Standings</h2>
    <div class="card">
      <ul class="leaderboard-list">
        @foreach($players->sortByDesc('score') as $idx => $player)
          <li class="leaderboard-item">
            <div class="leaderboard-rank">
              @if($idx==0) 🥇 @elseif($idx==1) 🥈 @elseif($idx==2) 🥉 @else {{ $idx+1 }} @endif
            </div>
            <div class="leaderboard-name">
              {{ $player->nickname }}
              @if($player->best_streak >= 3)<span style="font-size:.75rem;color:var(--qb-yellow);margin-left:.4rem">🔥{{ $player->best_streak }}</span>@endif
            </div>
            <div class="leaderboard-score">{{ number_format($player->score) }}</div>
          </li>
        @endforeach
      </ul>
    </div>
  </div>
</div>
@endsection
