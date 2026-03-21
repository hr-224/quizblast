@extends('layouts.app')
@section('title', 'New Quiz')

@section('content')
<div style="padding:3rem 0">
  <div class="container-sm">
    <a href="{{ route('dashboard') }}" class="text-muted" style="font-size:0.88rem">← Back to Dashboard</a>
    <h1 style="font-size:2rem;margin:1rem 0 0.25rem">Create a Quiz</h1>
    <p class="text-muted mb-3">Set a title and description, then add questions on the next screen.</p>

    <div class="card slide-up">
      <form method="POST" action="{{ route('quizzes.store') }}">
        @csrf
        <div class="form-group">
          <label class="form-label">Quiz Title *</label>
          <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="e.g. World Geography Trivia" required autofocus />
          @error('title')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
          <label class="form-label">Description (optional)</label>
          <textarea name="description" class="form-control" placeholder="Brief description of your quiz...">{{ old('description') }}</textarea>
          @error('description')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="form-group">
          <label class="form-check">
            <input type="checkbox" name="is_public" value="1" {{ old('is_public') ? 'checked' : '' }} />
            <span>Make this quiz public</span>
          </label>
        </div>
        <div style="display:flex;gap:0.75rem">
          <button type="submit" class="btn btn-primary btn-lg">Create & Add Questions →</button>
          <a href="{{ route('dashboard') }}" class="btn btn-outline btn-lg">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
