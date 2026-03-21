<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'QuizBlast') — QuizBlast</title>
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <link rel="shortcut icon" href="/favicon.svg" />
  <link rel="stylesheet" href="/css/app.css" />
  <style>
    .game-wrap { min-height: 100vh; display: flex; flex-direction: column; }
    .game-topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 1.25rem;
      height: 58px;
      background: var(--qb-purple);
      border-bottom: 3px solid rgba(0,0,0,0.3);
      flex-shrink: 0;
    }
    .game-topbar-brand {
      font-family: 'Montserrat', sans-serif;
      font-weight: 900;
      font-size: 1.15rem;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: -0.3px;
    }
    .game-topbar-brand span { color: var(--qb-yellow); }
  </style>
  @stack('head')
</head>
<body>
<div class="game-wrap">
  <div class="game-topbar">
    <span class="game-topbar-brand">⚡ Quiz<span>Blast</span></span>
    <div style="display:flex;gap:0.75rem;align-items:center">
      @yield('topbar-center')
    </div>
    <div>@yield('topbar-right')</div>
  </div>
  <div style="flex:1;display:flex;flex-direction:column">
    @yield('content')
  </div>
</div>
@stack('scripts')
</body>
</html>
