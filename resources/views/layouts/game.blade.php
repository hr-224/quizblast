<!DOCTYPE html>
<html lang="en">
<head>
  @include('partials.head')
  @stack('head')
</head>
<body>
<div class="game-wrap">
  <div class="game-topbar">
    <span class="game-topbar-brand">@include('partials.brand-mark')<span class="brand-word">Quiz<span>Blast</span></span></span>
    @hasSection('topbar-center')
    <div class="topbar-center">@yield('topbar-center')</div>
    @endif
    <div class="topbar-right">
      @yield('topbar-right')
      @include('partials.ui-toggles', ['mute' => true])
    </div>
  </div>
  <div class="game-body">
    @yield('content')
  </div>
</div>
<script src="/js/qb-ui.js?v={{ filemtime(public_path('js/qb-ui.js')) }}"></script>
@stack('scripts')
</body>
</html>
