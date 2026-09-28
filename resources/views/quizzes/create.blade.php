@extends('layouts.app')
@section('title', 'New Quiz')

@section('content')
<div class="container-sm mt-4">
  <a href="{{ route('dashboard') }}" class="text-muted">← Back to dashboard</a>
  <h1 class="mt-2">Create a quiz</h1>
  <p class="text-muted mb-3">Set a title and description, then add questions on the next screen.</p>

  <div class="card slide-up">
    <form method="POST" action="{{ route('quizzes.store') }}" enctype="multipart/form-data">
      @csrf
      <div class="form-group">
        <label class="form-label">Quiz title *</label>
        <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="e.g. World Geography Trivia" required autofocus />
        @error('title')<span class="field-error">{{ $message }}</span>@enderror
      </div>
      <div class="form-group">
        <label class="form-label">Description (optional)</label>
        <textarea name="description" class="form-control" placeholder="Brief description of your quiz...">{{ old('description') }}</textarea>
        @error('description')<span class="field-error">{{ $message }}</span>@enderror
      </div>
      <x-banner-fields />
      <div class="form-group">
        <label class="form-check">
          <input type="checkbox" name="is_public" value="1" {{ old('is_public') ? 'checked' : '' }} />
          <span>Make this quiz public</span>
        </label>
      </div>
      <div class="page-head-actions">
        <button type="submit" class="btn btn-primary btn-lg">Create &amp; add questions →</button>
        <a href="{{ route('dashboard') }}" class="btn btn-outline btn-lg">Cancel</a>
      </div>
    </form>
  </div>
</div>
@endsection
