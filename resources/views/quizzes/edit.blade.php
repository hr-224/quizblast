@extends('layouts.app')
@section('title', 'Edit Quiz')

@section('content')
<div class="container mt-4">
  <a href="{{ route('dashboard') }}" class="text-muted">← Dashboard</a>

  <div class="page-head">
    <div>
      <h1>{{ $quiz->title }}</h1>
      <p class="text-muted mt-1">{{ $quiz->questions->count() }} question{{ $quiz->questions->count() != 1 ? 's' : '' }}</p>
    </div>
    <div class="page-head-actions">
      <a href="{{ route('game.start', $quiz) }}" class="btn btn-success {{ $quiz->questions->isEmpty() ? 'is-disabled' : '' }}" @if($quiz->questions->isEmpty()) aria-disabled="true" tabindex="-1" @endif>▶ Host now</a>
      <form method="POST" action="{{ route('quizzes.duplicate', $quiz) }}" class="inline-form">
        @csrf
        <button type="submit" class="btn btn-outline">⧉ Duplicate</button>
      </form>
      <button type="button" class="btn btn-outline" id="settings-toggle" aria-expanded="false" aria-controls="edit-meta">⚙ Settings</button>
      <a href="{{ route('quizzes.embed', $quiz) }}" class="btn btn-outline">‹/› Embed</a>
      <form method="POST" action="{{ route('quizzes.destroy', $quiz) }}" class="inline-form" data-confirm="Delete this quiz? This cannot be undone.">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger">Delete</button>
      </form>
    </div>
  </div>

  <div id="edit-meta" class="card hidden mb-3">
    <form method="POST" action="{{ route('quizzes.update', $quiz) }}">
      @csrf @method('PUT')
      <div class="grid-2">
        <div class="form-group">
          <label class="form-label">Title</label>
          <input type="text" name="title" class="form-control" value="{{ old('title', $quiz->title) }}" required />
        </div>
        <div class="form-group">
          <label class="form-label">Category</label>
          <input type="text" name="category" class="form-control" value="{{ old('category', $quiz->category) }}" placeholder="e.g. Science, History..." />
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <input type="text" name="description" class="form-control" value="{{ old('description', $quiz->description) }}" />
        </div>
        <div class="form-group">
          <label class="form-label">Tags (comma separated)</label>
          <input type="text" name="tags" class="form-control" value="{{ old('tags', $quiz->tags) }}" placeholder="fun, trivia, easy..." />
        </div>
      </div>
      <div class="form-group">
        <label class="form-check">
          <input type="checkbox" name="is_public" value="1" {{ $quiz->is_public ? 'checked' : '' }} />
          <span>Make public in library</span>
        </label>
      </div>
      <button type="submit" class="btn btn-primary">Save settings</button>
    </form>
  </div>

  <div class="grid-2">
    <div>
      <h2 class="card-title mb-2">
        Questions ({{ $quiz->questions->count() }})
        @if($quiz->questions->count() > 1)<span class="field-hint">· drag to reorder</span>@endif
      </h2>

      <div id="questions-sortable">
        @if($quiz->questions->isEmpty())
          <div class="card q-empty text-center">
            <p class="text-muted">No questions yet. Add your first question →</p>
          </div>
        @else
          @foreach($quiz->questions as $idx => $question)
            <div class="card q-card mb-2" data-id="{{ $question->id }}">
              <div class="q-card-head">
                <span class="drag-handle" aria-hidden="true">⠿</span>
                <button type="button" class="q-card-summary" data-target="q-body-{{ $question->id }}" aria-expanded="false">
                  <div class="q-card-meta">
                    <span class="q-num">Q{{ $idx + 1 }}</span> · {{ $question->time_limit }}s · {{ $question->points }} pts
                    @if($question->multiple_correct)<span class="text-cyan"> · Multi</span>@endif
                    @if($question->image_url)<span class="text-yellow"> · Image</span>@endif
                    @if($question->video_url)<span class="text-yellow"> · Video</span>@endif
                  </div>
                  <div class="q-card-text">{{ $question->question_text }}</div>
                  <div class="q-card-chips">
                    @foreach($question->answers as $ans)
                      <x-answer-chip :text="$ans->answer_text" :correct="$ans->is_correct" />
                    @endforeach
                  </div>
                </button>
                <div class="q-card-actions">
                  <form method="POST" action="{{ route('quizzes.deleteQuestion', [$quiz, $question]) }}" class="inline-form" data-confirm="Remove this question?">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" aria-label="Delete question">✕</button>
                  </form>
                </div>
              </div>

              <div class="q-card-body hidden" id="q-body-{{ $question->id }}">
                <form method="POST" action="{{ route('quizzes.updateQuestion', [$quiz, $question]) }}">
                  @csrf @method('PUT')
                  <input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::random(32) }}" />
                  <div class="form-group">
                    <label class="form-label">Question *</label>
                    <textarea name="question_text" class="form-control" rows="2" required>{{ $question->question_text }}</textarea>
                  </div>
                  <div class="grid-2">
                    <div class="form-group">
                      <label class="form-label">Image URL</label>
                      <input type="url" name="image_url" class="form-control" value="{{ $question->image_url }}" placeholder="https://..." />
                    </div>
                    <div class="form-group">
                      <label class="form-label">YouTube URL</label>
                      <input type="url" name="video_url" class="form-control" value="{{ $question->video_url }}" placeholder="https://youtube.com/..." />
                    </div>
                  </div>
                  <div class="grid-3">
                    <div class="form-group">
                      <label class="form-label">Time limit</label>
                      <select name="time_limit" class="form-control">
                        @foreach([5,10,15,20,30,45,60,90,120] as $t)
                          <option value="{{ $t }}" {{ $question->time_limit == $t ? 'selected' : '' }}>{{ $t }}s</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="form-group">
                      <label class="form-label">Points</label>
                      <select name="points" class="form-control">
                        @foreach([0,100,250,500,1000,1500,2000] as $p)
                          <option value="{{ $p }}" {{ $question->points == $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="form-group">
                      <label class="form-label">Delay before answers</label>
                      <input type="number" name="answer_delay" class="form-control" min="0" max="180" value="{{ $question->answer_delay }}" />
                      <span class="field-hint">Give players time to watch a video or read before answers appear</span>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-check">
                      <input type="checkbox" name="multiple_correct" value="1" class="q-toggle-mode" {{ $question->multiple_correct ? 'checked' : '' }} />
                      <span>Multiple correct answers</span>
                    </label>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Answers <span class="q-mode-label field-hint">({{ $question->multiple_correct ? 'select ALL correct' : 'select ONE correct' }})</span></label>
                    @php $sorted = $question->answers->sortBy('order')->values(); @endphp
                    @for($i = 0; $i < 4; $i++)
                      @php $ans = $sorted->get($i); @endphp
                      <div class="q-answer-row">
                        <x-answer-shape :index="$i" class="ans-fg-{{ $i }}" />
                        <input type="radio" name="correct_answers[]" value="{{ $i }}" class="q-single" {{ $ans && $ans->is_correct && !$question->multiple_correct ? 'checked' : '' }} />
                        <input type="checkbox" name="correct_answers[]" value="{{ $i }}" class="q-multi" {{ $ans && $ans->is_correct && $question->multiple_correct ? 'checked' : '' }} />
                        <input type="text" name="answers[{{ $i }}]" class="form-control" value="{{ $ans ? $ans->answer_text : '' }}" placeholder="Answer {{ $i + 1 }}{{ $i < 2 ? ' *' : '' }}" {{ $i < 2 ? 'required' : '' }} />
                      </div>
                    @endfor
                  </div>
                  <div class="page-head-actions">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                    <button type="button" class="btn btn-outline q-card-collapse" data-target="q-body-{{ $question->id }}">Cancel</button>
                  </div>
                </form>
              </div>
            </div>
          @endforeach
        @endif
      </div>
    </div>

    <div>
      <h2 class="card-title mb-2">Add question</h2>
      <div class="card">
        <form method="POST" action="{{ route('quizzes.addQuestion', $quiz) }}">
          @csrf
          <input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::random(32) }}" />
          <div class="form-group">
            <label class="form-label">Question *</label>
            <textarea name="question_text" class="form-control" rows="2" placeholder="What is...?" required>{{ old('question_text') }}</textarea>
            @error('question_text')<span class="field-error">{{ $message }}</span>@enderror
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Image URL (optional)</label>
              <input type="url" name="image_url" class="form-control" value="{{ old('image_url') }}" placeholder="https://..." />
            </div>
            <div class="form-group">
              <label class="form-label">YouTube URL (optional)</label>
              <input type="url" name="video_url" class="form-control" value="{{ old('video_url') }}" placeholder="https://youtube.com/..." />
            </div>
          </div>
          <div class="grid-3">
            <div class="form-group">
              <label class="form-label">Time limit</label>
              <select name="time_limit" class="form-control">
                @foreach([5,10,15,20,30,45,60,90,120] as $t)
                  <option value="{{ $t }}" {{ old('time_limit', 20) == $t ? 'selected' : '' }}>{{ $t }}s</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Points</label>
              <select name="points" class="form-control">
                @foreach([0,100,250,500,1000,1500,2000] as $p)
                  <option value="{{ $p }}" {{ old('points', 1000) == $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Delay before answers</label>
              <input type="number" name="answer_delay" class="form-control" min="0" max="180" value="{{ old('answer_delay', 5) }}" />
              <span class="field-hint">Give players time to watch a video or read before answers appear</span>
            </div>
          </div>
          <div class="form-group">
            <label class="form-check">
              <input type="checkbox" name="multiple_correct" value="1" id="add-multi-toggle" class="q-toggle-mode" {{ old('multiple_correct') ? 'checked' : '' }} />
              <span>Multiple correct answers</span>
            </label>
          </div>
          <div class="form-group">
            <label class="form-label">Answers <span id="add-mode-label" class="q-mode-label field-hint">(select ONE correct)</span></label>
            @for($i = 0; $i < 4; $i++)
              <div class="q-answer-row">
                <x-answer-shape :index="$i" class="ans-fg-{{ $i }}" />
                <input type="radio" name="correct_answers[]" value="{{ $i }}" class="q-single" {{ old('correct_answers.0', 0) == $i ? 'checked' : '' }} />
                <input type="checkbox" name="correct_answers[]" value="{{ $i }}" class="q-multi" />
                <input type="text" name="answers[{{ $i }}]" class="form-control" value="{{ old('answers.' . $i) }}" placeholder="Answer {{ $i + 1 }}{{ $i < 2 ? ' *' : '' }}" {{ $i < 2 ? 'required' : '' }} />
              </div>
            @endfor
            @error('answers')<span class="field-error">{{ $message }}</span>@enderror
            @error('correct_answers')<span class="field-error">{{ $message }}</span>@enderror
          </div>
          <button type="submit" class="btn btn-primary btn-full">＋ Add question</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script>
(function () {
  // Settings panel toggle
  const settingsToggle = document.getElementById('settings-toggle');
  const settingsPanel  = document.getElementById('edit-meta');
  if (settingsToggle && settingsPanel) {
    settingsToggle.addEventListener('click', () => {
      const willShow = settingsPanel.classList.contains('hidden');
      settingsPanel.classList.toggle('hidden');
      settingsToggle.setAttribute('aria-expanded', willShow ? 'true' : 'false');
    });
  }

  // Collapsible question cards
  document.querySelectorAll('.q-card-summary').forEach(btn => {
    btn.addEventListener('click', () => {
      const body = document.getElementById(btn.dataset.target);
      if (!body) return;
      const willShow = body.classList.contains('hidden');
      body.classList.toggle('hidden');
      btn.setAttribute('aria-expanded', willShow ? 'true' : 'false');
    });
  });
  document.querySelectorAll('.q-card-collapse').forEach(btn => {
    btn.addEventListener('click', () => {
      const body = document.getElementById(btn.dataset.target);
      if (body) body.classList.add('hidden');
      const opener = document.querySelector('.q-card-summary[data-target="' + btn.dataset.target + '"]');
      if (opener) { opener.setAttribute('aria-expanded', 'false'); opener.focus(); }
    });
  });

  // Per-form single/multi correct-answer toggle (add form + every card's edit form)
  document.querySelectorAll('.q-toggle-mode').forEach(toggle => {
    const form = toggle.closest('form');
    if (!form) return;
    const label = form.querySelector('.q-mode-label');
    function update() {
      const isMulti = toggle.checked;
      if (label) label.textContent = isMulti ? '(select ALL correct)' : '(select ONE correct)';
      form.querySelectorAll('.q-single').forEach(el => { el.classList.toggle('hidden', isMulti); el.disabled = isMulti; });
      form.querySelectorAll('.q-multi').forEach(el => { el.classList.toggle('hidden', !isMulti); el.disabled = !isMulti; });
    }
    toggle.addEventListener('change', update);
    update();
  });

  // Drag to reorder
  const reorderUrl = '{{ route('quizzes.reorder', $quiz) }}';
  const csrfToken  = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  const sortableEl = document.getElementById('questions-sortable');
  if (sortableEl) {
    Sortable.create(sortableEl, {
      handle: '.drag-handle',
      animation: 180,
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      onEnd: async () => {
        const order = [...sortableEl.querySelectorAll('.q-card')].map(el => el.dataset.id);
        sortableEl.querySelectorAll('.q-num').forEach((el, i) => { el.textContent = 'Q' + (i + 1); });
        const requestToken = (window.crypto && window.crypto.randomUUID) ? window.crypto.randomUUID() : (Date.now() + '-' + Math.random());
        try {
          const res = await fetch(reorderUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ order, request_token: requestToken }),
          });
          if (!res.ok) window.alert('Could not save the new order — please refresh and try again.');
        } catch (e) {
          window.alert('Could not save the new order — please refresh and try again.');
        }
      },
    });
  }
})();
</script>
@endpush
