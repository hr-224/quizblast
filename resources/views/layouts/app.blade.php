<!DOCTYPE html>
<html lang="en">
<head>
  @include('partials.head')
  @stack('head')
</head>
<body>

<nav class="navbar">
  <div class="navbar-inner">
    <a href="{{ route('play.join') }}" class="navbar-brand">@include('partials.brand-mark')<span class="brand-word">Quiz<span>Blast</span></span></a>
    <div class="navbar-nav" id="nav-links">
      <a href="{{ route('library') }}" class="nav-link">Library</a>
      @auth
        <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
        <a href="{{ route('quizzes.create') }}" class="nav-link">New Quiz</a>
        <form method="POST" action="{{ route('logout') }}" class="inline-form">
          @csrf
          <button type="submit" class="btn btn-outline btn-sm">Log out</button>
        </form>
      @else
        <a href="{{ route('play.join') }}" class="nav-link">Join Game</a>
        @if(session('player_account_id'))
          <a href="{{ route('player.stats') }}" class="nav-link">My Stats</a>
          <form method="POST" action="{{ route('player.logout') }}" class="inline-form">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm">Log out</button>
          </form>
        @else
          <a href="{{ route('login') }}" class="nav-link">Sign In</a>
          <a href="{{ route('register') }}" class="btn btn-white btn-sm">Sign Up</a>
        @endif
      @endauth
    </div>{{-- /.navbar-nav #nav-links --}}
    @include('partials.ui-toggles')
    <button id="nav-toggle" class="hamburger-btn" aria-label="Menu" aria-expanded="false" aria-controls="nav-dropdown">☰</button>
  </div>{{-- /.navbar-inner --}}
  <div id="nav-dropdown" class="nav-dropdown" hidden>
    <a href="{{ route('library') }}" class="nav-dropdown-link">Library</a>
    @auth
      <a href="{{ route('dashboard') }}" class="nav-dropdown-link">Dashboard</a>
      <a href="{{ route('quizzes.create') }}" class="nav-dropdown-link">New Quiz</a>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="nav-dropdown-link nav-dropdown-btn">Log out</button>
      </form>
    @else
      <a href="{{ route('play.join') }}" class="nav-dropdown-link">Join Game</a>
      @if(session('player_account_id'))
        <a href="{{ route('player.stats') }}" class="nav-dropdown-link">My Stats</a>
        <form method="POST" action="{{ route('player.logout') }}">
          @csrf
          <button type="submit" class="nav-dropdown-link nav-dropdown-btn">Log out</button>
        </form>
      @else
        <a href="{{ route('login') }}" class="nav-dropdown-link">Sign In</a>
        <a href="{{ route('register') }}" class="nav-dropdown-link nav-dropdown-link-accent">Sign Up</a>
      @endif
    @endauth
  </div>
</nav>

<main>
  @if(session('success'))
    <div class="container mt-2"><div class="alert alert-success">{{ session('success') }}</div></div>
  @endif
  @if(session('error'))
    <div class="container mt-2"><div class="alert alert-error">{{ session('error') }}</div></div>
  @endif

  @yield('content')
</main>

<script src="/js/qb-confirm.js?v={{ filemtime(public_path('js/qb-confirm.js')) }}"></script>
<script src="/js/qb-ui.js?v={{ filemtime(public_path('js/qb-ui.js')) }}"></script>
@stack('scripts')
<script>
document.querySelectorAll('.alert').forEach(function(el) {
  setTimeout(function() {
    el.style.transition = 'opacity 0.5s ease';
    el.style.opacity = '0';
    setTimeout(function() { el.remove(); }, 500);
  }, 3000);
});

(function() {
  var toggle = document.getElementById('nav-toggle');
  var dropdown = document.getElementById('nav-dropdown');
  if (!toggle || !dropdown) return;
  toggle.addEventListener('click', function() {
    var isOpen = !dropdown.hidden;
    dropdown.hidden = isOpen;
    toggle.textContent = isOpen ? '☰' : '✕';
    toggle.setAttribute('aria-expanded', String(!isOpen));
  });
})();
</script>
</body>
</html>
