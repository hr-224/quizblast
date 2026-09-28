@extends('layouts.app', ['fxBg' => true])
@section('title', 'Live Quiz Games for Classrooms & Teams')

@section('content')
<section class="landing-hero">
  <div class="landing-hero-glow" aria-hidden="true"></div>
  <div class="container landing-hero-inner">
    <div class="landing-hero-copy">
      <h1 class="landing-hero-title">Live quizzes that get<br>everyone playing<span>.</span></h1>
      <p class="landing-hero-sub">Host a quiz on the big screen, players join from their phones, and everyone answers in real time. No app to install, no cost to play.</p>

      <form method="GET" action="{{ route('play.join') }}" class="landing-pin-form">
        <input type="text" name="pin" class="form-control landing-pin-input" id="landing-pin"
               placeholder="Enter game PIN" maxlength="6" inputmode="numeric" pattern="[0-9]*"
               autocomplete="off" aria-label="Game PIN" />
        <button type="submit" class="btn btn-primary btn-lg">Join game</button>
      </form>

      <div class="landing-hero-actions">
        <a href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="btn btn-outline btn-lg">Host a game — it's free</a>
        <a href="{{ route('library') }}" class="landing-inline-link">Browse the quiz library →</a>
      </div>
    </div>

    <div class="landing-hero-visual" aria-hidden="true">
      <div class="landing-tile landing-tile-0"><x-answer-shape :index="0" /></div>
      <div class="landing-tile landing-tile-1"><x-answer-shape :index="1" /></div>
      <div class="landing-tile landing-tile-2"><x-answer-shape :index="2" /></div>
      <div class="landing-tile landing-tile-3"><x-answer-shape :index="3" /></div>
    </div>
  </div>
</section>

<section class="landing-section">
  <div class="container">
    <h2 class="landing-section-title text-center">How it works</h2>
    <p class="landing-section-sub text-center">Two ways to play — pick a side.</p>

    <div class="landing-howto-cols">
      <div class="landing-howto-col">
        <div class="landing-howto-kicker">Playing</div>
        <ol class="landing-steps">
          <li class="landing-step">
            <span class="landing-step-num">1</span>
            <div><strong>Get a game PIN.</strong> The host displays it on the big screen when they launch a game.</div>
          </li>
          <li class="landing-step">
            <span class="landing-step-num">2</span>
            <div><strong>Join in seconds.</strong> Enter the PIN and a nickname — no account or app download needed.</div>
          </li>
          <li class="landing-step">
            <span class="landing-step-num">3</span>
            <div><strong>Answer live.</strong> Race the clock, climb the leaderboard, and use powerups to pull ahead.</div>
          </li>
        </ol>
      </div>

      <div class="landing-howto-col">
        <div class="landing-howto-kicker">Hosting</div>
        <ol class="landing-steps">
          <li class="landing-step">
            <span class="landing-step-num">1</span>
            <div><strong>Sign up free.</strong> Create a host account in a few seconds.</div>
          </li>
          <li class="landing-step">
            <span class="landing-step-num">2</span>
            <div><strong>Build or borrow a quiz.</strong> Write your own questions or pick one from the public library.</div>
          </li>
          <li class="landing-step">
            <span class="landing-step-num">3</span>
            <div><strong>Launch and share the PIN.</strong> Put it on a screen, watch players join the lobby, and go.</div>
          </li>
        </ol>
      </div>
    </div>
  </div>
</section>

<section class="landing-section landing-section-alt">
  <div class="container">
    <h2 class="landing-section-title text-center">Everything a live quiz needs</h2>
    <div class="grid-3 landing-feature-grid">
      <div class="card landing-feature">
        <div class="landing-feature-icon" style="color:var(--ans-cyan)"><x-answer-shape :index="1" /></div>
        <div class="landing-feature-title">Real-time leaderboard</div>
        <p class="text-muted">Scores update instantly after every question, with a speed bonus for fast, correct answers.</p>
      </div>
      <div class="card landing-feature">
        <div class="landing-feature-icon" style="color:var(--ans-amber)"><x-answer-shape :index="3" /></div>
        <div class="landing-feature-title">Powerups &amp; streaks</div>
        <p class="text-muted">Fifty-fifty, double points, and spy powerups add strategy on top of knowing the answer.</p>
      </div>
      <div class="card landing-feature">
        <div class="landing-feature-icon" style="color:var(--ans-lime)"><x-answer-shape :index="2" /></div>
        <div class="landing-feature-title">Team mode</div>
        <p class="text-muted">Play solo or group players into teams that share a combined score.</p>
      </div>
      <div class="card landing-feature">
        <div class="landing-feature-icon" style="color:var(--ans-coral)"><x-answer-shape :index="0" /></div>
        <div class="landing-feature-title">Public quiz library</div>
        <p class="text-muted">Browse quizzes other hosts have shared, or publish your own for others to play.</p>
      </div>
      <div class="card landing-feature">
        <div class="landing-feature-icon" style="color:var(--violet-text)"><x-answer-shape :index="1" /></div>
        <div class="landing-feature-title">Spectator screens</div>
        <p class="text-muted">Put a read-only view on a projector or TV so everyone can watch the action unfold.</p>
      </div>
      <div class="card landing-feature">
        <div class="landing-feature-icon" style="color:var(--ok)"><x-answer-shape :index="2" /></div>
        <div class="landing-feature-title">Works on any device</div>
        <p class="text-muted">Runs in the browser on phones, tablets, and laptops — nothing to install.</p>
      </div>
    </div>
  </div>
</section>

<section class="landing-cta">
  <div class="container landing-cta-inner">
    <h2 class="landing-cta-title">Ready to play?</h2>
    <p class="text-muted">Join a game with a PIN, or create a free account and host your own.</p>
    <div class="landing-cta-actions">
      <a href="{{ route('play.join') }}" class="btn btn-primary btn-xl">Join a game</a>
      <a href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="btn btn-outline btn-xl">Host a game</a>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
  var pin = document.getElementById('landing-pin');
  if (!pin) return;
  pin.addEventListener('input', function () {
    var v = pin.value.replace(/\D/g, '').slice(0, 6);
    if (v !== pin.value) pin.value = v;
  });
})();
</script>
@endpush
