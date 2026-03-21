@extends('layouts.app')
@section('title', 'Edit Quiz')

@section('content')
<div style="padding:2.5rem 0">
  <div class="container">
    <a href="{{ route('dashboard') }}" class="text-muted" style="font-size:0.88rem">← Dashboard</a>

    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1.5rem;margin:1.25rem 0;flex-wrap:wrap">
      <div>
        <h1 style="font-size:1.9rem;text-transform:uppercase;letter-spacing:-0.5px">{{ $quiz->title }}</h1>
        <p class="text-muted mt-1">{{ $quiz->questions->count() }} question{{ $quiz->questions->count() != 1 ? 's' : '' }}</p>
      </div>
      <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center">
        <a href="{{ route('game.start', $quiz) }}" class="btn btn-success" style="height:38px;width:130px;box-shadow:none;padding:0;justify-content:center;{{ $quiz->questions->isEmpty() ? 'opacity:.45;pointer-events:none' : '' }}">▶ HOST NOW</a>
        <form method="POST" action="{{ route('quizzes.duplicate', $quiz) }}" style="display:flex;margin:0">
          @csrf
          <button class="btn btn-outline" style="height:38px;width:130px;box-shadow:none;padding:0;justify-content:center">⧉ DUPLICATE</button>
        </form>
        <button class="btn btn-outline" style="height:38px;width:130px;box-shadow:none;padding:0;justify-content:center" onclick="document.getElementById('edit-meta').classList.toggle('hidden')">⚙ SETTINGS</button>
        <a href="{{ route('quizzes.embed', $quiz) }}" class="btn btn-outline" style="height:38px;width:130px;box-shadow:none;padding:0;justify-content:center">‹/› EMBED</a>
        <form method="POST" action="{{ route('quizzes.destroy', $quiz) }}" onsubmit="return confirm('Delete this quiz?')" style="display:flex;margin:0">
          @csrf @method('DELETE')
          <button class="btn btn-danger" style="height:38px;width:130px;box-shadow:none;padding:0;justify-content:center">DELETE</button>
        </form>
      </div>
    </div>

    {{-- Settings Panel --}}
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
        <button class="btn btn-primary">SAVE SETTINGS</button>
      </form>
    </div>


    <div class="grid-2" style="align-items:start;gap:2rem">

      {{-- Questions list --}}
      <div>
        <h2 style="font-size:0.85rem;font-weight:800;text-transform:uppercase;letter-spacing:.8px;color:var(--qb-muted);margin-bottom:0.75rem">
          Questions ({{ $quiz->questions->count() }})
          @if($quiz->questions->count() > 1)
            <span style="font-weight:400;color:var(--qb-muted);font-size:0.75rem;margin-left:0.5rem">· drag to reorder</span>
          @endif
        </h2>

        @if($quiz->questions->isEmpty())
          <div class="card text-center" style="padding:2.5rem;border:2px dashed rgba(255,255,255,0.1)">
            <p class="text-muted">No questions yet. Add your first question →</p>
          </div>
        @else
          <div id="questions-sortable">
            @foreach($quiz->questions as $idx => $question)
              <div class="card mb-2 sortable-item" data-id="{{ $question->id }}" style="padding:1rem 1.25rem;cursor:grab">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.5rem">
                  <div style="display:flex;align-items:flex-start;gap:0.75rem;flex:1">
                    <div class="drag-handle" style="color:var(--qb-muted);font-size:1.1rem;padding-top:0.1rem;cursor:grab;flex-shrink:0;user-select:none">⠿</div>
                    <div style="flex:1">
                      <div style="font-size:0.72rem;font-weight:700;color:var(--qb-muted);margin-bottom:0.3rem;text-transform:uppercase;letter-spacing:.5px">
                        <span class="q-num">Q{{ $idx+1 }}</span> · {{ $question->time_limit }}s · {{ $question->points }} pts
                        @if($question->multiple_correct)<span style="color:var(--qb-cyan)"> · MULTI</span>@endif
                        @if($question->image_url)<span style="color:var(--qb-yellow)"> · IMG</span>@endif
                        @if($question->video_url)<span style="color:var(--qb-green)"> · VIDEO</span>@endif
                      </div>
                      <div style="font-weight:700;font-size:0.9rem">{{ $question->question_text }}</div>
                      <div style="display:flex;flex-wrap:wrap;gap:0.35rem;margin-top:0.5rem">
                        @foreach($question->answers as $ans)
                          <span style="padding:.2rem .6rem;border-radius:var(--radius);font-size:0.75rem;font-weight:700;background:{{ $ans->is_correct ? 'rgba(38,137,12,0.2)' : 'rgba(255,255,255,0.06)' }};border:1px solid {{ $ans->is_correct ? 'rgba(38,137,12,0.4)' : 'var(--qb-border)' }};color:{{ $ans->is_correct ? '#5ddd3a' : 'var(--qb-text)' }}">
                            {{ $ans->is_correct ? '✓ ' : '' }}{{ $ans->answer_text }}
                          </span>
                        @endforeach
                      </div>
                    </div>
                  </div>
                  <div style="display:flex;flex-direction:column;gap:0.3rem">
                    <button type="button" class="btn btn-outline btn-sm" style="padding:.25rem .5rem" onclick="openEditModal({{ $question->id }})">✎</button>
                    <form method="POST" action="{{ route('quizzes.deleteQuestion', [$quiz, $question]) }}" onsubmit="return confirm('Remove?')">
                      @csrf @method('DELETE')
                      <button class="btn btn-danger btn-sm" style="padding:.25rem .5rem">✕</button>
                    </form>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>

      {{-- Add Question Form --}}
      <div>
        <h2 style="font-size:0.85rem;font-weight:800;text-transform:uppercase;letter-spacing:.8px;color:var(--qb-muted);margin-bottom:0.75rem">Add Question</h2>
        <div class="card">
          <form method="POST" action="{{ route('quizzes.addQuestion', $quiz) }}">
            @csrf

            <div class="form-group">
              <label class="form-label">Question *</label>
              <textarea name="question_text" class="form-control" rows="2" placeholder="What is...?" required>{{ old('question_text') }}</textarea>
              @error('question_text')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            {{-- Media --}}
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

            <div class="grid-2">
              <div class="form-group">
                <label class="form-label">Time Limit</label>
                <select name="time_limit" class="form-control">
                  @foreach([5,10,15,20,30,45,60,90,120] as $t)
                    <option value="{{ $t }}" {{ old('time_limit',20) == $t ? 'selected' : '' }}>{{ $t }}s</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Points</label>
                <select name="points" class="form-control">
                  @foreach([0,100,250,500,1000,1500,2000] as $p)
                    <option value="{{ $p }}" {{ old('points',1000) == $p ? 'selected' : '' }}>{{ $p }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-check">
                <input type="checkbox" name="multiple_correct" value="1" id="multi-toggle" {{ old('multiple_correct') ? 'checked' : '' }} />
                <span>Multiple correct answers</span>
              </label>
            </div>

            <div class="form-group">
              <label class="form-label">Answers <span id="answer-mode-label" style="color:var(--qb-muted);font-size:0.75rem">(select ONE correct)</span></label>
              @php $shapes = ['🔴','🔵','🟡','🟢']; @endphp
              @for($i = 0; $i < 4; $i++)
                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem">
                  <span style="font-size:1rem;flex-shrink:0">{{ $shapes[$i] }}</span>
                  {{-- Single mode: radio, Multi mode: checkbox --}}
                  <input type="radio" name="correct_answers[]" value="{{ $i }}" class="single-correct" {{ old('correct_answers.0', 0) == $i ? 'checked' : '' }} style="accent-color:var(--qb-purple3);width:18px;height:18px;flex-shrink:0;display:block" />
                  <input type="checkbox" name="correct_answers[]" value="{{ $i }}" class="multi-correct" style="accent-color:var(--qb-purple3);width:18px;height:18px;flex-shrink:0;display:none" />
                  <input type="text" name="answers[{{ $i }}]" class="form-control" value="{{ old('answers.'.$i) }}" placeholder="Answer {{ $i+1 }}{{ $i < 2 ? ' *' : '' }}" {{ $i < 2 ? 'required' : '' }} style="margin:0" />
                </div>
              @endfor
              @error('answers')<span class="field-error">{{ $message }}</span>@enderror
              @error('correct_answers')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <button type="submit" class="btn btn-primary btn-full">＋ ADD QUESTION</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Edit Question Modal --}}
<div id="edit-modal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.7);backdrop-filter:blur(4px);overflow-y:auto;padding:2rem 1rem">
  <div style="max-width:600px;margin:0 auto;background:var(--qb-card);border:2px solid var(--qb-border);border-radius:var(--radius);padding:1.75rem;position:relative">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
      <h2 style="font-size:1rem;text-transform:uppercase;letter-spacing:.5px">Edit Question</h2>
      <button onclick="closeEditModal()" style="background:none;border:none;color:var(--qb-muted);font-size:1.5rem;cursor:pointer;line-height:1">×</button>
    </div>
    <form id="edit-question-form" method="POST">
      @csrf @method('PUT')
      <div class="form-group">
        <label class="form-label">Question *</label>
        <textarea name="question_text" id="eq-text" class="form-control" rows="2" required></textarea>
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label class="form-label">Image URL</label>
          <input type="url" name="image_url" id="eq-image" class="form-control" placeholder="https://..." />
        </div>
        <div class="form-group">
          <label class="form-label">YouTube URL</label>
          <input type="url" name="video_url" id="eq-video" class="form-control" placeholder="https://youtube.com/..." />
        </div>
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label class="form-label">Time Limit</label>
          <select name="time_limit" id="eq-time" class="form-control">
            @foreach([5,10,15,20,30,45,60,90,120] as $t)
              <option value="{{ $t }}">{{ $t }}s</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Points</label>
          <select name="points" id="eq-points" class="form-control">
            @foreach([0,100,250,500,1000,1500,2000] as $p)
              <option value="{{ $p }}">{{ $p }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-check">
          <input type="checkbox" name="multiple_correct" id="eq-multi" value="1" />
          <span>Multiple correct answers</span>
        </label>
      </div>
      <div class="form-group">
        <label class="form-label">Answers <span id="eq-mode-label" style="color:var(--qb-muted);font-size:.75rem">(select ONE correct)</span></label>
        @php $shapes = ['🔴','🔵','🟡','🟢']; @endphp
        @for($i = 0; $i < 4; $i++)
          <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem">
            <span style="font-size:1rem;flex-shrink:0">{{ $shapes[$i] }}</span>
            <input type="radio"    name="correct_answers[]" value="{{ $i }}" class="eq-single" style="width:18px;height:18px;accent-color:var(--qb-purple3);flex-shrink:0" />
            <input type="checkbox" name="correct_answers[]" value="{{ $i }}" class="eq-multi"  style="width:18px;height:18px;accent-color:var(--qb-purple3);flex-shrink:0;display:none" />
            <input type="text" name="answers[{{ $i }}]" id="eq-ans-{{ $i }}" class="form-control" placeholder="Answer {{ $i+1 }}{{ $i < 2 ? ' *' : '' }}" {{ $i < 2 ? 'required' : '' }} style="margin:0" />
          </div>
        @endfor
      </div>
      <div style="display:flex;gap:.75rem">
        <button type="submit" class="btn btn-primary btn-lg">SAVE CHANGES</button>
        <button type="button" onclick="closeEditModal()" class="btn btn-outline btn-lg">CANCEL</button>
      </div>
    </form>
  </div>
</div>


@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script>
// ── Drag to reorder ──────────────────────────────────────────────────────
const reorderUrl = '{{ route("quizzes.reorder", $quiz) }}';
const csrfToken  = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const sortableEl = document.getElementById('questions-sortable');

if (sortableEl) {
  Sortable.create(sortableEl, {
    handle: '.drag-handle',
    animation: 180,
    ghostClass: 'sortable-ghost',
    chosenClass: 'sortable-chosen',
    onEnd: async function() {
      const order = [...sortableEl.querySelectorAll('.sortable-item')].map(el => el.dataset.id);
      sortableEl.querySelectorAll('.q-num').forEach((el, i) => { el.textContent = 'Q' + (i + 1); });
      try {
        await fetch(reorderUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
          body: JSON.stringify({ order })
        });
      } catch(e) { console.error('Reorder failed', e); }
    }
  });
}

// ── Add question: multiple correct toggle ────────────────────────────────
const toggle = document.getElementById('multi-toggle');
const label  = document.getElementById('answer-mode-label');

function updateMode() {
  const isMulti = toggle.checked;
  label.textContent = isMulti ? '(select ALL correct answers)' : '(select ONE correct)';
  document.querySelectorAll('.single-correct').forEach(el => el.style.display = isMulti ? 'none' : 'block');
  document.querySelectorAll('.multi-correct').forEach(el => el.style.display = isMulti ? 'block' : 'none');
}

if (toggle) { toggle.addEventListener('change', updateMode); updateMode(); }

// ── Edit modal ───────────────────────────────────────────────────────────
const questionData = {
  @foreach($quiz->questions as $question)
  {{ $question->id }}: {
    text:     @json($question->question_text),
    time:     {{ $question->time_limit }},
    points:   {{ $question->points }},
    image:    @json($question->image_url),
    video:    @json($question->video_url),
    multiple: {{ $question->multiple_correct ? 'true' : 'false' }},
    updateUrl:'{{ route("quizzes.updateQuestion", [$quiz, $question]) }}',
    answers:  [
      @foreach($question->answers->sortBy('order') as $ans)
      { text: @json($ans->answer_text), correct: {{ $ans->is_correct ? 'true' : 'false' }}, order: {{ $ans->order }} },
      @endforeach
    ],
  },
  @endforeach
};

function openEditModal(id) {
  const q = questionData[id];
  if (!q) return;
  document.getElementById('eq-text').value   = q.text;
  document.getElementById('eq-image').value  = q.image || '';
  document.getElementById('eq-video').value  = q.video || '';
  document.getElementById('eq-time').value   = q.time;
  document.getElementById('eq-points').value = q.points;
  document.getElementById('eq-multi').checked = q.multiple;
  const sorted = q.answers.sort((a,b) => a.order - b.order);
  for (let i = 0; i < 4; i++) {
    const ans = sorted[i];
    document.getElementById('eq-ans-' + i).value = ans ? ans.text : '';
    const radio = document.querySelectorAll('.eq-single')[i];
    const check = document.querySelectorAll('.eq-multi')[i];
    if (radio) radio.checked = ans ? (!q.multiple && ans.correct) : false;
    if (check) check.checked = ans ? (q.multiple && ans.correct) : false;
  }
  updateEditMode();
  document.getElementById('edit-question-form').action = q.updateUrl;
  document.getElementById('edit-modal').style.display = '';
  document.body.style.overflow = 'hidden';
}

function closeEditModal() {
  document.getElementById('edit-modal').style.display = 'none';
  document.body.style.overflow = '';
}

const eqMulti = document.getElementById('eq-multi');
function updateEditMode() {
  const isMulti = eqMulti.checked;
  document.getElementById('eq-mode-label').textContent = isMulti ? '(select ALL correct)' : '(select ONE correct)';
  document.querySelectorAll('.eq-single').forEach(el => el.style.display = isMulti ? 'none' : '');
  document.querySelectorAll('.eq-multi').forEach(el => el.style.display = isMulti ? '' : 'none');
}
if (eqMulti) eqMulti.addEventListener('change', updateEditMode);

document.getElementById('edit-modal')?.addEventListener('click', function(e) {
  if (e.target === this) closeEditModal();
});
</script>
<style>
.sortable-ghost  { opacity:.35; background:rgba(108,62,232,.15)!important; border-color:rgba(108,62,232,.4)!important; }
.sortable-chosen { box-shadow:0 8px 24px rgba(0,0,0,.4); cursor:grabbing!important; }
.drag-handle:hover { color:var(--qb-text); }
</style>
@endpush