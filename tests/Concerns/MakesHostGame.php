<?php

namespace Tests\Concerns;

use App\Models\Answer;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Collection;

trait MakesHostGame
{
    /**
     * @return array{host: User, quiz: Quiz, game: Game, questions: Collection, players: Collection}
     */
    protected function makeHostGame(string $status = 'waiting', int $questionCount = 2, int $playerCount = 2, string $pin = '555001'): array
    {
        $host = User::create(['name' => 'Host', 'email' => "hostgame{$pin}@test.com", 'password' => 'secret-pass']);
        $quiz = Quiz::create(['user_id' => $host->id, 'title' => 'Host Quiz']);

        $questions = collect();
        for ($i = 0; $i < $questionCount; $i++) {
            $q = Question::create([
                'quiz_id' => $quiz->id, 'question_text' => "Question number {$i}?",
                'time_limit' => 20, 'points' => 1000, 'order' => $i,
            ]);
            foreach ([['Alpha', false], ['Bravo', true], ['Charlie', false], ['Delta', false]] as $a => [$text, $correct]) {
                Answer::create(['question_id' => $q->id, 'answer_text' => $text, 'is_correct' => $correct, 'order' => $a]);
            }
            $questions->push($q->load('answers'));
        }

        $game = Game::create([
            'quiz_id' => $quiz->id, 'user_id' => $host->id, 'pin' => $pin,
            'status' => $status, 'current_question' => 0,
            'question_started_at' => $status === 'question' ? now() : null,
        ]);

        $players = collect();
        for ($p = 1; $p <= $playerCount; $p++) {
            $players->push(GamePlayer::create([
                'game_id' => $game->id, 'nickname' => "Player{$p}", 'score' => 0,
                'streak' => 0, 'best_streak' => 0, 'session_id' => "sess{$p}",
            ]));
        }

        return compact('host', 'quiz', 'game', 'questions', 'players');
    }
}
