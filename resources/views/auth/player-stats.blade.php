@extends('layouts.app')
@section('title', 'My Stats')

@section('content')
<div class="stats-hero">
  <div class="container page-head">
    <div>
      <h1>{{ $account->name }}'s stats</h1>
      <p class="text-muted">{{ $account->email }}</p>
    </div>
    <form method="POST" action="{{ route('player.logout') }}" class="inline-form" data-confirm="Log out?">
      @csrf
      <button type="submit" class="btn btn-outline btn-sm">Log out</button>
    </form>
  </div>
</div>

<div class="container-md mt-3">
  <div class="grid-4 mb-4 stats-summary">
    <div class="card text-center stats-stat">
      <div class="field-hint">Games played</div>
      <div class="stats-stat-val">{{ $account->games_played }}</div>
    </div>
    <div class="card text-center stats-stat">
      <div class="field-hint">Wins</div>
      <div class="stats-stat-val is-amber">{{ $account->wins }}</div>
    </div>
    <div class="card text-center stats-stat">
      <div class="field-hint">Win rate</div>
      <div class="stats-stat-val is-ok">{{ $account->win_rate }}</div>
    </div>
    <div class="card text-center stats-stat">
      <div class="field-hint">Total points</div>
      <div class="stats-stat-val is-cyan">{{ number_format($account->total_score) }}</div>
    </div>
  </div>

  <h2 class="card-title mb-2">Recent games</h2>

  @if($recentGames->isEmpty())
    <div class="card q-empty text-center">
      <p class="text-muted">No games played yet. <a href="{{ route('play.join') }}">Join a game</a> to get started!</p>
    </div>
  @else
    <div class="card card-flush">
      @foreach($recentGames as $gp)
        <div class="q-preview-row q-preview-row-card{{ $loop->last ? '' : ' q-preview-row-divider' }}">
          <div class="q-preview-body">
            <span class="q-card-text">{{ $gp->game->quiz->title ?? 'Unknown quiz' }}</span>
            <span class="text-muted">as {{ $gp->nickname }} · {{ $gp->created_at->diffForHumans() }}@if($gp->best_streak >= 3) · 🔥{{ $gp->best_streak }} streak @endif</span>
          </div>
          <div class="stats-stat-val is-amber">{{ number_format($gp->score) }}</div>
        </div>
      @endforeach
    </div>
  @endif

  <div class="text-center mt-3">
    <a href="{{ route('play.join') }}" class="btn btn-success btn-lg">Join a game</a>
  </div>
</div>
@endsection
