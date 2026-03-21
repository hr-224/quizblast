<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $quizzes = $request->user()->quizzes()->withCount('questions')->latest()->get();
        return view('quizzes.index', compact('quizzes'));
    }

    public function create()
    {
        return view('quizzes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => ['required','string','max:255'],
            'description' => ['nullable','string','max:1000'],
            'category'    => ['nullable','string','max:100'],
            'tags'        => ['nullable','string','max:255'],
            'is_public'   => ['nullable','boolean'],
        ]);

        $quiz = $request->user()->quizzes()->create([
            'title'       => $request->title,
            'description' => $request->description,
            'category'    => $request->category,
            'tags'        => $request->tags,
            'is_public'   => $request->boolean('is_public'),
        ]);

        return redirect()->route('quizzes.edit', $quiz)->with('success', 'Quiz created! Now add some questions.');
    }

    public function show(Quiz $quiz)
    {
        $this->authorize($quiz);
        $quiz->load('questions.answers');
        return view('quizzes.show', compact('quiz'));
    }

    public function edit(Quiz $quiz)
    {
        $this->authorize($quiz);
        $quiz->load('questions.answers');
        return view('quizzes.edit', compact('quiz'));
    }

    public function update(Request $request, Quiz $quiz)
    {
        $this->authorize($quiz);
        $request->validate([
            'title'       => ['required','string','max:255'],
            'description' => ['nullable','string','max:1000'],
            'category'    => ['nullable','string','max:100'],
            'tags'        => ['nullable','string','max:255'],
            'is_public'   => ['nullable','boolean'],
        ]);

        $quiz->update([
            'title'       => $request->title,
            'description' => $request->description,
            'category'    => $request->category,
            'tags'        => $request->tags,
            'is_public'   => $request->boolean('is_public'),
        ]);

        return back()->with('success', 'Quiz updated!');
    }

    public function destroy(Quiz $quiz)
    {
        $this->authorize($quiz);
        $quiz->delete();
        return redirect()->route('dashboard')->with('success', 'Quiz deleted.');
    }

    public function duplicate(Quiz $quiz)
    {
        $this->authorize($quiz);
        $new = $quiz->duplicate();
        return redirect()->route('quizzes.edit', $new)->with('success', 'Quiz duplicated!');
    }

    public function addQuestion(Request $request, Quiz $quiz)
    {
        $this->authorize($quiz);

        $request->validate([
            'question_text'    => ['required','string','max:500'],
            'time_limit'       => ['required','integer','min:5','max:120'],
            'points'           => ['required','integer','min:0','max:2000'],
            'answers'          => ['required','array','min:2','max:4'],
            'answers.*'        => ['required','string','max:200'],
            'correct_answers'  => ['required','array','min:1'],
            'image_url'        => ['nullable','url:http,https','max:500'],
            'video_url'        => ['nullable','url:http,https','max:500'],
            'multiple_correct' => ['nullable','boolean'],
        ]);

        $order    = $quiz->questions()->count();
        $multiple = $request->boolean('multiple_correct') || count($request->correct_answers) > 1;

        $question = $quiz->questions()->create([
            'question_text'    => $request->question_text,
            'time_limit'       => $request->time_limit,
            'points'           => $request->points,
            'order'            => $order,
            'image_url'        => $request->image_url,
            'video_url'        => $request->video_url,
            'multiple_correct' => $multiple,
        ]);

        $correctAnswers = array_map('intval', $request->correct_answers);

        foreach ($request->answers as $idx => $answerText) {
            if (trim($answerText) === '') continue;
            $question->answers()->create([
                'answer_text' => $answerText,
                'is_correct'  => in_array($idx, $correctAnswers),
                'order'       => $idx,
            ]);
        }

        return back()->with('success', 'Question added!');
    }

    public function deleteQuestion(Quiz $quiz, Question $question)
    {
        $this->authorize($quiz);
        abort_if($question->quiz_id !== $quiz->id, 404);
        $question->delete();
        // Re-order remaining
        $quiz->questions()->orderBy('order')->get()->each(function ($q, $i) {
            $q->update(['order' => $i]);
        });
        return back()->with('success', 'Question removed.');
    }

    public function moveQuestion(Request $request, Quiz $quiz, Question $question)
    {
        $this->authorize($quiz);
        abort_if($question->quiz_id !== $quiz->id, 404);

        $direction = $request->input('direction'); // 'up' or 'down'
        $questions = $quiz->questions()->orderBy('order')->get();
        $idx       = $questions->search(fn($q) => $q->id === $question->id);

        $swapIdx = $direction === 'up' ? $idx - 1 : $idx + 1;

        if ($swapIdx < 0 || $swapIdx >= $questions->count()) {
            return back();
        }

        $swap = $questions[$swapIdx];
        $question->update(['order' => $swapIdx]);
        $swap->update(['order' => $idx]);

        return back()->with('success', 'Question reordered.');
    }

    public function updateQuestion(Request $request, Quiz $quiz, Question $question)
    {
        $this->authorize($quiz);
        abort_if($question->quiz_id !== $quiz->id, 404);

        $request->validate([
            'question_text'    => ['required','string','max:500'],
            'time_limit'       => ['required','integer','min:5','max:120'],
            'points'           => ['required','integer','min:0','max:2000'],
            'answers'          => ['required','array','min:2','max:4'],
            'answers.*'        => ['required','string','max:200'],
            'correct_answers'  => ['required','array','min:1'],
            'image_url'        => ['nullable','url:http,https','max:500'],
            'video_url'        => ['nullable','url:http,https','max:500'],
            'multiple_correct' => ['nullable','boolean'],
        ]);

        $multiple = $request->boolean('multiple_correct') || count($request->correct_answers) > 1;

        $question->update([
            'question_text'    => $request->question_text,
            'time_limit'       => $request->time_limit,
            'points'           => $request->points,
            'image_url'        => $request->image_url,
            'video_url'        => $request->video_url,
            'multiple_correct' => $multiple,
        ]);

        // Delete existing answers and recreate
        $question->answers()->delete();
        $correctAnswers = array_map('intval', $request->correct_answers);
        foreach ($request->answers as $idx => $answerText) {
            if (trim($answerText) === '') continue;
            $question->answers()->create([
                'answer_text' => $answerText,
                'is_correct'  => in_array($idx, $correctAnswers),
                'order'       => $idx,
            ]);
        }

        return back()->with('success', 'Question updated!');
    }

    public function reorderQuestions(Request $request, Quiz $quiz)
    {
        $this->authorize($quiz);
        $request->validate(['order' => ['required', 'array']]);
        foreach ($request->order as $idx => $questionId) {
            $quiz->questions()->where('id', $questionId)->update(['order' => $idx]);
        }
        return response()->json(['ok' => true]);
    }

    public function embed(Quiz $quiz)
    {
        $this->authorize($quiz);
        $quiz->load('questions.answers');
        return view('quizzes.show', compact('quiz'));
    }

    private function authorize(Quiz $quiz)
    {
        if ($quiz->user_id !== auth()->id()) abort(403);
    }
}
