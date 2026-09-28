@extends('layouts.app')
@section('title', 'Sign Up')

@section('content')
<div class="auth-page">
  <div class="auth-card slide-up">
    <h1 class="auth-title">Create account</h1>
    <p class="auth-sub">Join QuizBlast as a host or player</p>

    <form method="POST" action="{{ route('register') }}">
      @csrf
      <input type="hidden" name="account_type" id="account_type" value="{{ old('account_type', 'player') }}" />

      <div class="account-type-toggle">
        <button type="button" class="account-type-btn {{ old('account_type', 'player') === 'player' ? 'is-active' : '' }}" data-type="player">🎮 Player</button>
        <button type="button" class="account-type-btn {{ old('account_type') === 'host' ? 'is-active' : '' }}" data-type="host">🎤 Host</button>
      </div>

      <p class="account-type-hint" id="type-hint-player" @if(old('account_type') === 'host') hidden @endif>Track your stats and game history across all QuizBlast games.</p>
      <p class="account-type-hint" id="type-hint-host" @if(old('account_type') !== 'host') hidden @endif>Create and host live quizzes for your audience.</p>

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

@push('scripts')
<script>
(function () {
  const typeInput = document.getElementById('account_type');
  const hintPlayer = document.getElementById('type-hint-player');
  const hintHost = document.getElementById('type-hint-host');

  document.querySelectorAll('.account-type-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const type = btn.dataset.type;
      typeInput.value = type;
      document.querySelectorAll('.account-type-btn').forEach(b => b.classList.toggle('is-active', b === btn));
      hintPlayer.hidden = type !== 'player';
      hintHost.hidden = type !== 'host';
    });
  });
})();
</script>
@endpush
