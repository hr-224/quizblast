@extends('layouts.app')
@section('title', 'Player Login')
@section('content')
<div style="min-height:calc(100vh - 63px);display:flex;align-items:center;justify-content:center;background:linear-gradient(160deg,var(--qb-purple) 0%,var(--qb-darker) 55%);padding:2rem 1rem">
  <div style="width:100%;max-width:420px">
    <div style="text-align:center;margin-bottom:1.75rem">
      <h1 style="font-size:1.8rem;text-transform:uppercase;letter-spacing:-.5px">Player Login</h1>
      <p style="color:rgba(255,255,255,.55);margin-top:.4rem;font-size:.9rem">Log in to track your stats</p>
    </div>
    <div class="card slide-up" style="border:2px solid rgba(255,255,255,.12)">
      <form method="POST" action="{{ route('player.login') }}">
        @csrf
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus />
          @error('email')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required />
        </div>
        <button type="submit" class="btn btn-success btn-full btn-lg" style="letter-spacing:1px">LOG IN</button>
      </form>
    </div>
    <p class="text-center mt-3" style="color:rgba(255,255,255,.5);font-size:.85rem">
      No account? <a href="{{ route('player.register') }}" style="color:var(--qb-yellow);font-weight:700">Create one free</a>
    </p>
  </div>
</div>
@endsection
