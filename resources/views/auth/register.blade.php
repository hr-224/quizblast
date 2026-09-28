@extends('layouts.app', ['fxBg' => true])
@section('title', 'Sign Up')

@section('content')
<div class="auth-page">
  <div class="auth-card slide-up">
    <h1 class="auth-title">Create account</h1>
    <p class="auth-sub">Host live quizzes and track your stats — all from one account.</p>

    <form method="POST" action="{{ route('register') }}">
      @csrf

      <div class="form-group">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Your name" required autofocus />
        @error('name')<span class="field-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="you@example.com" required />
        @error('email')<span class="field-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="At least 8 characters" required />
        @error('password')<span class="field-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label">Confirm password</label>
        <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat your password" required />
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-lg">Create account</button>
    </form>

    <p class="auth-footer">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
  </div>
</div>
@endsection
