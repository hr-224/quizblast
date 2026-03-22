@extends('layouts.app')
@section('title', 'Sign Up')

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
  width: 100%; max-width: 460px;
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
.type-toggle { display: flex; gap: 8px; margin-bottom: 1.5rem; }
.type-btn {
  flex: 1; padding: .6rem .5rem;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 6px; color: rgba(255,255,255,.6);
  font-family: 'Montserrat', sans-serif;
  font-weight: 700; font-size: .8rem;
  text-transform: uppercase; letter-spacing: .5px;
  cursor: pointer; transition: all .15s; text-align: center;
}
.type-btn.active {
  background: rgba(255,208,0,.15);
  border-color: #ffd000;
  color: #ffd000;
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
  <div style="width:100%;max-width:460px;position:relative;z-index:2">

    <div style="text-align:center;margin-bottom:1.75rem">
      <div class="auth-title">Create Account</div>
      <p style="color:rgba(255,255,255,.5);margin-top:.4rem;font-size:.9rem">Join QuizBlast as a host or player</p>
    </div>

    <div class="auth-card slide-up">
      <form method="POST" action="{{ route('register') }}" id="reg-form">
        @csrf
        <input type="hidden" name="account_type" id="account_type" value="{{ old('account_type', 'player') }}" />

        <div class="type-toggle">
          <button type="button" class="type-btn {{ old('account_type', 'player') === 'player' ? 'active' : '' }}"
                  onclick="setType('player')">🎮 Player</button>
          <button type="button" class="type-btn {{ old('account_type') === 'host' ? 'active' : '' }}"
                  onclick="setType('host')">🎤 Host</button>
        </div>

        <div id="type-hint-player" style="{{ old('account_type') === 'host' ? 'display:none' : '' }}">
          <p style="font-size:.8rem;color:var(--qb-muted);margin-bottom:1rem;background:rgba(255,255,255,.04);border-radius:6px;padding:.6rem .8rem">
            Track your stats and game history across all QuizBlast games.
          </p>
        </div>
        <div id="type-hint-host" style="{{ old('account_type') === 'host' ? '' : 'display:none' }}">
          <p style="font-size:.8rem;color:var(--qb-muted);margin-bottom:1rem;background:rgba(255,255,255,.04);border-radius:6px;padding:.6rem .8rem">
            Create and host live quizzes for your audience.
          </p>
        </div>

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
          <label class="form-label">Confirm Password</label>
          <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat your password" required />
        </div>

        <button type="submit" class="auth-submit" id="reg-btn">CREATE ACCOUNT</button>
      </form>
    </div>

    <p style="text-align:center;margin-top:1.25rem;color:rgba(255,255,255,.45);font-size:.85rem">
      Already have an account? <a href="{{ route('login') }}" style="color:var(--qb-yellow);font-weight:700">Sign in</a>
    </p>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
  function setType(type) {
    document.getElementById('account_type').value = type;
    document.querySelectorAll('.type-btn').forEach(function(b) { b.classList.remove('active'); });
    event.currentTarget.classList.add('active');
    document.getElementById('type-hint-player').style.display = type === 'player' ? '' : 'none';
    document.getElementById('type-hint-host').style.display   = type === 'host'   ? '' : 'none';
  }
  window.setType = setType;
})();
</script>
@endpush
