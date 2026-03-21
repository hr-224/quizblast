@extends('layouts.app')
@section('title', 'My Stats')
@section('content')
<div style="background:var(--qb-purple);padding:2rem 0;border-bottom:3px solid rgba(0,0,0,.3)">
  <div class="container">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
      <div>
        <h1 style="font-size:1.8rem;text-transform:uppercase;letter-spacing:-.5px">{{ $account->name }}'s Stats</h1>
        <p style="color:rgba(255,255,255,.6);font-size:.88rem;margin-top:.2rem">{{ $account->email }}</p>
      </div>
      <form method="POST" action="{{ route('player.logout') }}">
        @csrf
        <button class="btn btn-outline btn-sm">LOG OUT</button>
      </form>
    </div>
  </div>
</div>

<div style="padding:2rem 0">
  <div class="container-md">

    {{-- Summary --}}
    <div class="grid-4 mb-4">
      <div class="card text-center" style="padding:1.2rem">
        <div style="font-size:.7rem;color:var(--qb-muted);text-transform:uppercase;margin-bottom:.3rem">Games Played</div>
        <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:2rem">{{ $account->games_played }}</div>
      </div>
      <div class="card text-center" style="padding:1.2rem">
        <div style="font-size:.7rem;color:var(--qb-muted);text-transform:uppercase;margin-bottom:.3rem">Wins</div>
        <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:2rem;color:var(--qb-yellow)">{{ $account->wins }}</div>
      </div>
      <div class="card text-center" style="padding:1.2rem">
        <div style="font-size:.7rem;color:var(--qb-muted);text-transform:uppercase;margin-bottom:.3rem">Win Rate</div>
        <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:2rem;color:#5ddd3a">{{ $account->win_rate }}</div>
      </div>
      <div class="card text-center" style="padding:1.2rem">
        <div style="font-size:.7rem;color:var(--qb-muted);text-transform:uppercase;margin-bottom:.3rem">Total Points</div>
        <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:2rem;color:var(--qb-cyan)">{{ number_format($account->total_score) }}</div>
      </div>
    </div>

    {{-- Recent games --}}
    <h2 style="font-size:.85rem;font-weight:800;text-transform:uppercase;letter-spacing:.8px;color:var(--qb-muted);margin-bottom:.75rem">Recent Games</h2>

    @if($recentGames->isEmpty())
      <div class="card text-center" style="padding:2rem">
        <p class="text-muted">No games played yet. <a href="{{ route('play.join') }}" style="color:var(--qb-yellow)">Join a game</a> to get started!</p>
      </div>
    @else
      <div class="card" style="padding:0;overflow:hidden">
        @foreach($recentGames as $gp)
          <div style="padding:.85rem 1.25rem;border-bottom:1px solid var(--qb-border);display:flex;align-items:center;justify-content:space-between;@if($loop->last)border:none@endif">
            <div>
              <div style="font-weight:700;font-size:.9rem;text-transform:uppercase">{{ $gp->game->quiz->title ?? 'Unknown Quiz' }}</div>
              <div style="font-size:.78rem;color:var(--qb-muted);margin-top:.15rem">
                as <strong>{{ $gp->nickname }}</strong> · {{ $gp->created_at->diffForHumans() }}
                @if($gp->best_streak >= 3)<span style="color:var(--qb-yellow)"> · 🔥{{ $gp->best_streak }} streak</span>@endif
              </div>
            </div>
            <div style="font-family:'Montserrat',sans-serif;font-weight:900;font-size:1.2rem;color:var(--qb-yellow)">{{ number_format($gp->score) }}</div>
          </div>
        @endforeach
      </div>
    @endif

    <div class="mt-3 text-center">
      <a href="{{ route('play.join') }}" class="btn btn-success btn-lg">JOIN A GAME</a>
    </div>
  </div>
</div>
@endsection
