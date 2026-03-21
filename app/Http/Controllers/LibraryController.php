<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    public function index(Request $request)
    {
        $query = Quiz::where('is_public', true)->withCount('questions')->with('user');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('category', 'like', '%' . $request->search . '%')
                  ->orWhere('tags', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        $quizzes    = $query->latest()->paginate(18);
        $categories = Quiz::where('is_public', true)->whereNotNull('category')->distinct()->pluck('category');

        return view('library.index', compact('quizzes', 'categories'));
    }

    public function show(Quiz $quiz)
    {
        abort_if(!$quiz->is_public, 404);
        $quiz->load('questions.answers', 'user');
        return view('library.show', compact('quiz'));
    }
}
