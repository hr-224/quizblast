<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name'     => 'Demo Host',
            'email'    => 'demo@quizblast.app',
            'password' => Hash::make('password'),
        ]);

        $quiz = Quiz::create([
            'user_id'     => $user->id,
            'title'       => 'General Knowledge Blast',
            'description' => 'A mix of fun trivia questions',
            'is_public'   => true,
        ]);

        $questions = [
            [
                'text'    => 'What is the capital of France?',
                'time'    => 20,
                'points'  => 1000,
                'answers' => [
                    ['text' => 'Paris',    'correct' => true],
                    ['text' => 'London',   'correct' => false],
                    ['text' => 'Berlin',   'correct' => false],
                    ['text' => 'Madrid',   'correct' => false],
                ],
            ],
            [
                'text'    => 'How many planets are in our solar system?',
                'time'    => 15,
                'points'  => 1000,
                'answers' => [
                    ['text' => '7',  'correct' => false],
                    ['text' => '8',  'correct' => true],
                    ['text' => '9',  'correct' => false],
                    ['text' => '10', 'correct' => false],
                ],
            ],
            [
                'text'    => 'Which element has the chemical symbol "Au"?',
                'time'    => 20,
                'points'  => 1000,
                'answers' => [
                    ['text' => 'Silver',  'correct' => false],
                    ['text' => 'Gold',    'correct' => true],
                    ['text' => 'Copper',  'correct' => false],
                    ['text' => 'Iron',    'correct' => false],
                ],
            ],
            [
                'text'    => 'What year did World War II end?',
                'time'    => 20,
                'points'  => 1000,
                'answers' => [
                    ['text' => '1943', 'correct' => false],
                    ['text' => '1944', 'correct' => false],
                    ['text' => '1945', 'correct' => true],
                    ['text' => '1946', 'correct' => false],
                ],
            ],
            [
                'text'    => 'Which programming language is known as the "language of the web"?',
                'time'    => 15,
                'points'  => 1000,
                'answers' => [
                    ['text' => 'Python',     'correct' => false],
                    ['text' => 'JavaScript', 'correct' => true],
                    ['text' => 'Java',       'correct' => false],
                    ['text' => 'PHP',        'correct' => false],
                ],
            ],
        ];

        foreach ($questions as $idx => $qData) {
            $question = Question::create([
                'quiz_id'       => $quiz->id,
                'question_text' => $qData['text'],
                'time_limit'    => $qData['time'],
                'points'        => $qData['points'],
                'order'         => $idx,
            ]);
            foreach ($qData['answers'] as $aIdx => $aData) {
                Answer::create([
                    'question_id' => $question->id,
                    'answer_text' => $aData['text'],
                    'is_correct'  => $aData['correct'],
                    'order'       => $aIdx,
                ]);
            }
        }
    }
}
