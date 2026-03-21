@extends('layouts.app')
@section('title', $quiz->title . ' — Embed')

@section('content')
<div style="padding:2.5rem 0">
  <div class="container-md">
    <a href="{{ route('quizzes.edit', $quiz) }}" class="text-muted" style="font-size:.88rem">← Back to Editor</a>

    <h1 style="font-size:1.8rem;text-transform:uppercase;letter-spacing:-.5px;margin:1.25rem 0 .25rem">{{ $quiz->title }}</h1>
    <p class="text-muted mb-3">{{ $quiz->questions->count() }} questions</p>

    <div class="card mb-3">
      <div class="card-header">
        <div class="card-title">EMBED THIS QUIZ</div>
      </div>
      <p class="text-muted mb-3" style="font-size:.88rem">Add this quiz to any website. Players can join directly from the embedded widget without leaving your page.</p>

      <div class="form-group">
        <label class="form-label">Embed Code</label>
        <textarea class="form-control" id="embed-code" rows="4" readonly style="font-family:monospace;font-size:.82rem">{{ '<iframe src="' . route('play.join') . '?pin=LIVE_PIN&embed=1" width="100%" height="600" frameborder="0" style="border-radius:8px;box-shadow:0 4px 24px rgba(0,0,0,.3)" allow="autoplay"></iframe>' }}</textarea>
        <p class="text-muted mt-1" style="font-size:.78rem">⚠ Replace <code style="color:var(--qb-yellow)">LIVE_PIN</code> with the actual game PIN after you start hosting.</p>
      </div>

      <button class="btn btn-outline btn-sm" onclick="
        document.getElementById('embed-code').select();
        document.execCommand('copy');
        this.textContent='✓ Copied!';
        setTimeout(()=>this.textContent='Copy Code',2000)
      ">Copy Code</button>
    </div>

    <div class="card mb-3">
      <div class="card-header"><div class="card-title">DIRECT LINK</div></div>
      <p class="text-muted mb-2" style="font-size:.88rem">Share this link so players can join directly from any browser.</p>
      <div style="display:flex;gap:.75rem;align-items:center">
        <input type="text" class="form-control" id="direct-link" value="{{ route('play.join') }}" readonly />
        <button class="btn btn-outline btn-sm" onclick="
          document.getElementById('direct-link').select();
          document.execCommand('copy');
          this.textContent='✓ Copied!';
          setTimeout(()=>this.textContent='Copy',2000)
        " style="white-space:nowrap">Copy</button>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">HOW TO USE</div></div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem">
        <div style="text-align:center">
          <div style="font-size:2rem;margin-bottom:.5rem">1️⃣</div>
          <div style="font-weight:700;font-size:.88rem;text-transform:uppercase;margin-bottom:.3rem">Host the Quiz</div>
          <p class="text-muted" style="font-size:.8rem">Click "Host Now" on your quiz to generate a live game PIN</p>
        </div>
        <div style="text-align:center">
          <div style="font-size:2rem;margin-bottom:.5rem">2️⃣</div>
          <div style="font-weight:700;font-size:.88rem;text-transform:uppercase;margin-bottom:.3rem">Update the PIN</div>
          <p class="text-muted" style="font-size:.8rem">Replace LIVE_PIN in the embed code with your active game PIN</p>
        </div>
        <div style="text-align:center">
          <div style="font-size:2rem;margin-bottom:.5rem">3️⃣</div>
          <div style="font-weight:700;font-size:.88rem;text-transform:uppercase;margin-bottom:.3rem">Players Join</div>
          <p class="text-muted" style="font-size:.8rem">Players can join directly from your embedded widget</p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
