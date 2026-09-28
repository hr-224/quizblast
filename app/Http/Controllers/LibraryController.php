<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    private const SORTS = ['new', 'popular', 'questions'];

    /** Public quizzes with the counts every library card needs. */
    private function cards()
    {
        return Quiz::where('is_public', true)
            ->withCount('questions')
            ->withCount(['games as plays_count' => fn ($q) => $q->where('status', 'finished')])
            ->with('user');
    }

    public function index(Request $request)
    {
        $sort     = in_array($request->sort, self::SORTS, true) ? $request->sort : 'new';
        $search   = trim((string) $request->search);
        $category = (string) $request->category;

        $query = $this->cards();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('category', 'like', '%' . $search . '%')
                  ->orWhere('tags', 'like', '%' . $search . '%');
            });
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        match ($sort) {
            'popular'   => $query->orderByDesc('plays_count'),
            'questions' => $query->orderByDesc('questions_count'),
            default     => null,
        };
        $quizzes = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(18);

        // Category chips: name => number of public quizzes, biggest first.
        $categories = Quiz::where('is_public', true)
            ->whereNotNull('category')->where('category', '!=', '')
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')->orderByDesc('total')->orderBy('category')
            ->pluck('total', 'category');

        // Browse mode is the unfiltered front page; any filter, sort or later page shows just results.
        $browse = $search === '' && $category === '' && $sort === 'new' && $request->integer('page', 1) <= 1;

        $featured = $featuredLabel = $rows = null;
        if ($browse) {
            $featured      = $this->cards()->where('is_public', true)->having('plays_count', '>', 0)
                ->orderByDesc('plays_count')->orderByDesc('id')->limit(3)->get();
            $featuredLabel = 'Popular';
            if ($featured->isEmpty()) {
                $featured      = $this->cards()->latest()->orderByDesc('id')->limit(3)->get();
                $featuredLabel = 'Newest';
            }

            // A row only earns its place with at least two quizzes; a lone quiz is already in the grid below.
            $rows = $categories->filter(fn ($total) => $total >= 2)->take(3)->map(fn ($total, $name) => [
                'name'    => $name,
                'total'   => $total,
                'quizzes' => $this->cards()->where('category', $name)
                    ->orderByDesc('plays_count')->orderByDesc('id')->limit(3)->get(),
            ])->values();
        }

        return view('library.index', compact('quizzes', 'categories', 'sort', 'search', 'category', 'browse', 'featured', 'featuredLabel', 'rows'));
    }

    public function show(Quiz $quiz)
    {
        abort_if(!$quiz->is_public, 404);
        $quiz->load('questions.answers', 'user');
        return view('library.show', compact('quiz'));
    }
}
