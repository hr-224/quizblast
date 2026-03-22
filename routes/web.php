<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PlayerAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\SpectatorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () { return redirect()->route('play.join'); });

// Host Auth
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Player Accounts — legacy redirects keep old URLs working
Route::get('/account/register', fn() => redirect()->route('register'))->name('player.register');
Route::get('/account/login', fn() => redirect()->route('login'))->name('player.login');
Route::post('/account/register', [PlayerAuthController::class, 'register']);
Route::post('/account/login', [PlayerAuthController::class, 'login']);
Route::post('/account/logout', [PlayerAuthController::class, 'logout'])->name('player.logout');
Route::get('/account/stats', [PlayerAuthController::class, 'stats'])->name('player.stats');

// Public Library
Route::get('/library', [LibraryController::class, 'index'])->name('library');
Route::get('/library/{quiz}', [LibraryController::class, 'show'])->name('library.show');

// Host Dashboard
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/history/{game}', [DashboardController::class, 'gameHistory'])->name('dashboard.history');

    Route::resource('quizzes', QuizController::class);
    Route::get('/quizzes/{quiz}/embed', [QuizController::class, 'embed'])->name('quizzes.embed');
    Route::post('/quizzes/{quiz}/question', [QuizController::class, 'addQuestion'])->name('quizzes.addQuestion');
    Route::delete('/quizzes/{quiz}/question/{question}', [QuizController::class, 'deleteQuestion'])->name('quizzes.deleteQuestion');
    Route::post('/quizzes/{quiz}/question/{question}/move', [QuizController::class, 'moveQuestion'])->name('quizzes.moveQuestion');
    Route::post('/quizzes/{quiz}/reorder', [QuizController::class, 'reorderQuestions'])->name('quizzes.reorder');
    Route::put('/quizzes/{quiz}/question/{question}', [QuizController::class, 'updateQuestion'])->name('quizzes.updateQuestion');
    Route::post('/quizzes/{quiz}/duplicate', [QuizController::class, 'duplicate'])->name('quizzes.duplicate');

    Route::get('/host/{quiz}/start', [GameController::class, 'start'])->name('game.start');
    Route::get('/host/{game}/lobby', [GameController::class, 'lobby'])->name('game.lobby');
    Route::post('/host/{game}/launch', [GameController::class, 'launch'])->name('game.launch');
    Route::post('/host/{game}/next', [GameController::class, 'next'])->name('game.next');
    Route::post('/host/{game}/reveal', [GameController::class, 'reveal'])->name('game.reveal');
    Route::post('/host/{game}/skip', [GameController::class, 'skip'])->name('game.skip');
    Route::get('/host/{game}/question', [GameController::class, 'showQuestion'])->name('game.question');
    Route::get('/host/{game}/final', [GameController::class, 'final'])->name('game.final');
    Route::post('/host/{game}/end', [GameController::class, 'end'])->name('game.end');
});

// Spectator
Route::get('/spectate/{pin}', [SpectatorController::class, 'join'])->name('spectate.join');
Route::get('/spectate/{pin}/watch', [SpectatorController::class, 'watch'])->name('spectate.watch');

// Player routes
Route::get('/play', [PlayerController::class, 'joinForm'])->name('play.join');
Route::post('/play/join', [PlayerController::class, 'join'])->name('play.join.post');
Route::get('/play/{pin}/lobby', [PlayerController::class, 'lobby'])->name('play.lobby');
Route::get('/play/{pin}/game', [PlayerController::class, 'game'])->name('play.game');
Route::post('/play/{pin}/answer', [PlayerController::class, 'submitAnswer'])->name('play.answer');
Route::get('/play/{pin}/final', [PlayerController::class, 'final'])->name('play.final');
Route::post('/play/{pin}/heartbeat', [PlayerController::class, 'heartbeat'])->name('play.heartbeat');
Route::post('/play/{pin}/leave', [PlayerController::class, 'leave'])->name('play.leave');
Route::post('/play/{pin}/react', [PlayerController::class, 'react'])->name('play.react');
Route::post('/play/{pin}/powerup', [PlayerController::class, 'usePowerUp'])->name('play.powerup');
Route::get('/play/{pin}/spy', [PlayerController::class, 'spy'])->name('play.spy');
Route::delete('/play/{pin}/kick/{player}', [PlayerController::class, 'kick'])->name('play.kick')->middleware('auth');

// Polling API
Route::get('/api/game/{pin}/state', [GameController::class, 'state'])->name('api.game.state')->middleware('throttle:60,1')->where('pin', '[0-9]{6}');
Route::get('/api/game/{pin}/players', [GameController::class, 'players'])->name('api.game.players')->middleware('throttle:60,1')->where('pin', '[0-9]{6}');
