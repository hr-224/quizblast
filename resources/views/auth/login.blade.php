@extends('layouts.app')
@section('title', 'Log In')

@section('content')
<div style="min-height:calc(100vh - 63px);display:flex;align-items:center;justify-content:center;background:linear-gradient(160deg, var(--qb-purple) 0%, var(--qb-darker) 55%);padding:2rem 1rem">
  <div style="width:100%;max-width:420px">

    <div style="text-align:center;margin-bottom:1.75rem">
      <h1 style="font-size:2rem;text-transform:uppercase;letter-spacing:-0.5px">Welcome Back</h1>
      <p style="color:rgba(255,255,255,0.55);margin-top:0.4rem;font-size:0.9rem">Log in to host your quizzes</p>
    </div>

    <div class="card slide-up" style="border:2px solid rgba(255,255,255,0.12)">
      <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="you@example.com" required autofocus />
          @error('email')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required />
          @error('password')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
          <label class="form-check">
            <input type="checkbox" name="remember" />
            <span style="font-size:0.88rem;color:var(--qb-muted)">Remember me</span>
          </label>
        </div>
        <button type="submit" class="btn btn-success btn-full btn-lg" style="letter-spacing:1px">LOG IN</button>
      </form>
    </div>

    <p class="text-center mt-3" style="color:rgba(255,255,255,0.5);font-size:0.85rem">
      No account? <a href="{{ route('register') }}" style="color:var(--qb-yellow);font-weight:700">Sign up free</a>
    </p>
  </div>
</div>
@endsection
