@extends('layouts.app')
@section('title', $quiz->title . ' — Embed')

@section('content')
<div class="container-md mt-4">
  <a href="{{ route('quizzes.edit', $quiz) }}" class="text-muted">← Back to editor</a>
  <h1 class="mt-2">{{ $quiz->title }}</h1>
  <p class="text-muted mb-3">{{ $quiz->questions->count() }} questions</p>

  <div class="card mb-3">
    <div class="card-header"><div class="card-title">Embed this quiz</div></div>
    <p class="text-muted mb-3">Add this quiz to any website. Players can join directly from the embedded widget without leaving your page.</p>
    <div class="form-group">
      <label class="form-label">Embed code</label>
      <textarea class="form-control" id="embed-code" rows="4" readonly>{{ '<iframe src="' . route('play.join') . '?pin=LIVE_PIN&embed=1" width="100%" height="600" frameborder="0" allow="autoplay"></iframe>' }}</textarea>
      <p class="field-hint">⚠ Replace <code>LIVE_PIN</code> with the actual game PIN after you start hosting.</p>
    </div>
    <button type="button" class="btn btn-outline btn-sm" id="copy-embed">Copy code</button>
  </div>

  <div class="card mb-3">
    <div class="card-header"><div class="card-title">Direct link</div></div>
    <p class="text-muted mb-2">Share this link so players can join directly from any browser.</p>
    <div class="page-head-actions">
      <input type="text" class="form-control" id="direct-link" value="{{ route('play.join') }}" readonly />
      <button type="button" class="btn btn-outline btn-sm" id="copy-link">Copy</button>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">How to use</div></div>
    <div class="grid-3 embed-steps">
      <div class="embed-step">
        <div class="embed-step-num">1</div>
        <div class="embed-step-title">Host the quiz</div>
        <p class="text-muted">Click "Host Now" on your quiz to generate a live game PIN</p>
      </div>
      <div class="embed-step">
        <div class="embed-step-num">2</div>
        <div class="embed-step-title">Update the PIN</div>
        <p class="text-muted">Replace LIVE_PIN in the embed code with your active game PIN</p>
      </div>
      <div class="embed-step">
        <div class="embed-step-num">3</div>
        <div class="embed-step-title">Players join</div>
        <p class="text-muted">Players can join directly from your embedded widget</p>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  function copy(id, btn, label) {
    const el = document.getElementById(id);
    if (!el) return;
    el.select();
    document.execCommand('copy');
    const original = btn.textContent;
    btn.textContent = '✓ Copied!';
    setTimeout(() => { btn.textContent = original; }, 2000);
  }
  const copyEmbed = document.getElementById('copy-embed');
  const copyLink  = document.getElementById('copy-link');
  if (copyEmbed) copyEmbed.addEventListener('click', () => copy('embed-code', copyEmbed));
  if (copyLink) copyLink.addEventListener('click', () => copy('direct-link', copyLink));
})();
</script>
@endpush
