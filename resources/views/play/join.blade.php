@extends('layouts.app', ['fxBg' => true])
@section('title', 'Join a Game')

@section('content')
<div class="join-page">
  <div class="join-card slide-up">

    <div class="join-mark">@include('partials.brand-mark')</div>
    <h1 class="join-title">Quiz<span>Blast</span></h1>
    <p class="join-sub">Enter the PIN shown on the big screen</p>

    @if(request('kicked'))
      <div class="alert alert-error">You were kicked from the game.</div>
    @endif

    <form method="POST" action="{{ route('play.join.post') }}" class="join-form">
      @csrf
      <input type="hidden" name="rejoin_token" id="join-token" value="" />
      <div class="form-group">
        <label class="form-label" for="join-pin">Game PIN</label>
        <input type="text" id="join-pin" name="pin" class="form-control join-pin"
               value="{{ old('pin', request('pin')) }}"
               placeholder="000000" maxlength="6" inputmode="numeric" pattern="[0-9]*"
               autocomplete="off" autofocus required />
        @error('pin')
          <span class="field-error">{{ $message }}</span>
        @enderror
      </div>
      <div class="form-group">
        <label class="form-label" for="join-nick">Nickname</label>
        <input type="text" id="join-nick" name="nickname" class="form-control join-nick"
               value="{{ old('nickname') }}"
               placeholder="Pick a nickname" maxlength="20" autocomplete="off" required />
        @error('nickname')
          <span class="field-error">{{ $message }}</span>
        @enderror
      </div>
      <button type="submit" class="btn btn-primary btn-lg btn-full">Join game</button>
    </form>

    @unless(auth()->check())
      <div class="join-footer">
        <span>Want to host, or save your stats? <a href="{{ route('register') }}">Create an account</a></span>
      </div>
    @endunless
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var pin = document.getElementById('join-pin');
  if (!pin) return;
  pin.addEventListener('input', function () {
    var v = pin.value.replace(/\D/g, '').slice(0, 6);
    if (v !== pin.value) pin.value = v;
  });

  // Lets a player who lost their session prove they own their nickname mid-game.
  var form = pin.form, token = document.getElementById('join-token');
  if (form && token) {
    form.addEventListener('submit', function () {
      try { token.value = localStorage.getItem('qb_rejoin_' + pin.value) || ''; } catch (e) {}
    });
  }
})();
</script>
@endpush
