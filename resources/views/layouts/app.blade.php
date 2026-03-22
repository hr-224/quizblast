<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'QuizBlast') — QuizBlast</title>
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <link rel="shortcut icon" href="/favicon.svg" />
  <link rel="stylesheet" href="/css/app.css?v={{ filemtime(public_path('css/app.css')) }}" />
  @stack('head')
</head>
<body>

<nav class="navbar">
  <div class="navbar-inner">
    <a href="{{ route('play.join') }}" class="navbar-brand">⚡ Quiz<span>Blast</span></a>
    <div class="navbar-nav" id="nav-links">
      <a href="{{ route('library') }}" class="nav-link">Library</a>
      @auth
        <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
        <a href="{{ route('quizzes.create') }}" class="nav-link">New Quiz</a>
        <form method="POST" action="{{ route('logout') }}" style="display:inline">
          @csrf
          <button type="submit" class="btn btn-outline btn-sm">Log out</button>
        </form>
      @else
        <a href="{{ route('play.join') }}" class="nav-link">Join Game</a>
        @if(session('player_account_id'))
          <a href="{{ route('player.stats') }}" class="nav-link">My Stats</a>
          <form method="POST" action="{{ route('player.logout') }}" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm">Log out</button>
          </form>
        @else
          <a href="{{ route('login') }}" class="nav-link">Sign In</a>
          <a href="{{ route('register') }}" class="btn btn-white btn-sm">Sign Up</a>
        @endif
      @endauth
    </div>{{-- /.navbar-nav #nav-links --}}
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
        <a href="{{ route('register') }}" class="nav-dropdown-link" style="color:var(--qb-yellow)">Sign Up</a>
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
