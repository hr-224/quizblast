@extends('layouts.app')
@section('title', 'Sign In')

@section('content')
<div class="auth-page">
  <div class="auth-card slide-up">
    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-sub">Sign in to your host or player account</p>

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
          <span>Remember me</span>
        </label>
      </div>
      <button type="submit" class="btn btn-primary btn-full btn-lg">Sign in</button>
    </form>

    <p class="auth-footer">No account? <a href="{{ route('register') }}">Sign up free</a></p>
  </div>
</div>
@endsection
