@extends('layouts.app')
@section('title', 'Sign In')

@push('head')
<style>
.auth-bg {
  min-height: calc(100vh - 63px);
  display: flex; align-items: center; justify-content: center;
  background: #0a0010;
  padding: 2rem 1rem;
  position: relative;
  overflow: hidden;
}
.auth-bg::before {
  content: '';
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse 60% 45% at 20% 30%, rgba(120,60,220,.28) 0%, transparent 70%),
    radial-gradient(ellipse 50% 40% at 80% 70%, rgba(80,30,160,.22) 0%, transparent 65%),
    radial-gradient(ellipse 40% 35% at 55% 15%, rgba(160,80,255,.15) 0%, transparent 60%);
  pointer-events: none;
}
.auth-card {
  position: relative; z-index: 2;
  width: 100%; max-width: 420px;
  background: rgba(255,255,255,.065);
  backdrop-filter: blur(22px);
  -webkit-backdrop-filter: blur(22px);
  border: 1px solid rgba(255,208,0,.25);
  border-radius: 12px;
  padding: 2.4rem 2rem 2rem;
  box-shadow: 0 8px 48px rgba(0,0,0,.6), 0 0 40px rgba(255,208,0,.05);
}
.auth-title {
  font-family: 'Montserrat', sans-serif;
  font-size: 2rem; font-weight: 900;
  text-transform: uppercase; letter-spacing: -0.5px;
  background: linear-gradient(135deg, #ffd000 0%, #fff 35%, #c084fc 70%, #a855f7 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
}
.auth-submit {
  width: 100%;
  background: #ffd000; color: #1a0533;
  font-family: 'Montserrat', sans-serif;
  font-weight: 900; font-size: 1rem;
  letter-spacing: 1.5px; text-transform: uppercase;
  border: none; border-radius: 6px;
  padding: .75rem 1.5rem; cursor: pointer;
  box-shadow: 0 4px 24px rgba(255,208,0,.4);
  transition: opacity .15s, transform .1s;
  margin-top: .25rem;
}
.auth-submit:hover { opacity: .9; transform: translateY(-1px); }
.auth-submit:active { transform: translateY(0); }
</style>
@endpush

@section('content')
<div class="auth-bg">
  <div style="width:100%;max-width:420px;position:relative;z-index:2">

    <div style="text-align:center;margin-bottom:1.75rem">
      <div class="auth-title">Welcome Back</div>
      <p style="color:rgba(255,255,255,.5);margin-top:.4rem;font-size:.9rem">Sign in to your host or player account</p>
    </div>

    <div class="auth-card slide-up">
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
            <span style="font-size:.88rem;color:var(--qb-muted)">Remember me</span>
          </label>
        </div>
        <button type="submit" class="auth-submit">SIGN IN</button>
      </form>
    </div>

    <p style="text-align:center;margin-top:1.25rem;color:rgba(255,255,255,.45);font-size:.85rem">
      No account? <a href="{{ route('register') }}" style="color:var(--qb-yellow);font-weight:700">Sign up free</a>
    </p>
  </div>
</div>
@endsection
