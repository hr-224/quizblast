<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use Illuminate\Support\Facades\Hash;

class SampleQuizzesSeeder extends Seeder
{
    public function run(): void
    {
        // Find the first existing user, or create a default host account
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name'     => 'HR224',
                'email'    => 'admin@quizblast.app',
                'password' => Hash::make('changeme123'),
            ]);
            $this->command?->info("Created host account: admin@quizblast.app / changeme123 — change this password after logging in.");
        }
        $userId = $user->id;

        $quizzes = $this->quizData();
        foreach ($quizzes as $quizDef) {
            $quiz = Quiz::create([
                'user_id'     => $userId,
                'title'       => $quizDef['title'],
                'description' => $quizDef['description'],
                'category'    => $quizDef['category'],
                'tags'        => $quizDef['tags'],
                'is_public'   => true,
            ]);
            foreach ($quizDef['questions'] as $order => $qDef) {
                $question = Question::create([
                    'quiz_id'          => $quiz->id,
                    'question_text'    => $qDef['text'],
                    'image_url'        => $qDef['image_url'] ?? null,
                    'video_url'        => $qDef['video_url'] ?? null,
                    'multiple_correct' => $qDef['multiple_correct'] ?? false,
                    'time_limit'       => $qDef['time_limit'] ?? 20,
                    'points'           => $qDef['points'] ?? 1000,
                    'order'            => $order,
                ]);
                foreach ($qDef['answers'] as $aOrder => $aDef) {
                    Answer::create([
                        'question_id' => $question->id,
                        'answer_text' => $aDef['text'],
                        'is_correct'  => $aDef['correct'],
                        'order'       => $aOrder,
                    ]);
                }
            }
        }
    }

    private function quizData(): array
    {
        return [

            // ─────────────────────────────────────────────
            // 1. SCIENCE & NATURE
            // ─────────────────────────────────────────────
            [
                'title'       => 'Science Smackdown',
                'description' => 'From atoms to galaxies — test your science IQ!',
                'category'    => 'Science',
                'tags'        => 'science,nature,space,biology',
                'questions'   => [
                    [
                        'text'     => 'What is the chemical symbol for gold?',
                        'image_url'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a8/NatgoldUSGOV.jpg/320px-NatgoldUSGOV.jpg',
                        'answers'  => [
                            ['text'=>'Gd','correct'=>false],
                            ['text'=>'Go','correct'=>false],
                            ['text'=>'Au','correct'=>true],
                            ['text'=>'Ag','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'How many bones are in the adult human body?',
                        'answers'  => [
                            ['text'=>'206','correct'=>true],
                            ['text'=>'196','correct'=>false],
                            ['text'=>'215','correct'=>false],
                            ['text'=>'230','correct'=>false],
                        ],
                        'time_limit'=> 15,
                    ],
                    [
                        'text'     => 'What planet is known as the Red Planet?',
                        'video_url'=> 'https://www.youtube.com/watch?v=D8pnmwOXhoY',
                        'answers'  => [
                            ['text'=>'Venus','correct'=>false],
                            ['text'=>'Jupiter','correct'=>false],
                            ['text'=>'Saturn','correct'=>false],
                            ['text'=>'Mars','correct'=>true],
                        ],
                    ],
                    [
                        'text'     => 'What is the speed of light (approx.) in a vacuum?',
                        'answers'  => [
                            ['text'=>'300,000 km/s','correct'=>true],
                            ['text'=>'150,000 km/s','correct'=>false],
                            ['text'=>'450,000 km/s','correct'=>false],
                            ['text'=>'3,000 km/s','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which gas do plants absorb from the atmosphere?',
                        'image_url'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/41/Simple_photosynthesis_overview.svg/320px-Simple_photosynthesis_overview.svg.png',
                        'answers'  => [
                            ['text'=>'Oxygen','correct'=>false],
                            ['text'=>'Carbon dioxide','correct'=>true],
                            ['text'=>'Nitrogen','correct'=>false],
                            ['text'=>'Hydrogen','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'DNA stands for…',
                        'answers'  => [
                            ['text'=>'Deoxyribonucleic acid','correct'=>true],
                            ['text'=>'Dinitrogen acid','correct'=>false],
                            ['text'=>'Dynamic nuclear acid','correct'=>false],
                            ['text'=>'Deoxyribose nitrogen acid','correct'=>false],
                        ],
                        'time_limit'=> 25,
                        'points'   => 2000,
                    ],
                    [
                        'text'     => 'Which of these are states of matter? (select all)',
                        'multiple_correct' => true,
                        'answers'  => [
                            ['text'=>'Solid','correct'=>true],
                            ['text'=>'Plasma','correct'=>true],
                            ['text'=>'Shadow','correct'=>false],
                            ['text'=>'Liquid','correct'=>true],
                        ],
                        'time_limit'=> 25,
                    ],
                    [
                        'text'     => 'What force keeps planets in orbit around the Sun?',
                        'answers'  => [
                            ['text'=>'Magnetism','correct'=>false],
                            ['text'=>'Gravity','correct'=>true],
                            ['text'=>'Nuclear force','correct'=>false],
                            ['text'=>'Friction','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'What is the powerhouse of the cell?',
                        'answers'  => [
                            ['text'=>'Nucleus','correct'=>false],
                            ['text'=>'Ribosome','correct'=>false],
                            ['text'=>'Mitochondria','correct'=>true],
                            ['text'=>'Golgi apparatus','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which planet has the most moons?',
                        'video_url'=> 'https://www.youtube.com/watch?v=libKVRa01L8',
                        'answers'  => [
                            ['text'=>'Jupiter','correct'=>false],
                            ['text'=>'Uranus','correct'=>false],
                            ['text'=>'Neptune','correct'=>false],
                            ['text'=>'Saturn','correct'=>true],
                        ],
                        'time_limit'=> 20,
                    ],
                ],
            ],

            // ─────────────────────────────────────────────
            // 2. GEOGRAPHY
            // ─────────────────────────────────────────────
            [
                'title'       => 'World Geography Challenge',
                'description' => 'Capitals, continents, and countries from every corner of the globe.',
                'category'    => 'Geography',
                'tags'        => 'geography,world,countries,capitals',
                'questions'   => [
                    [
                        'text'     => 'What is the capital of Australia?',
                        'answers'  => [
                            ['text'=>'Sydney','correct'=>false],
                            ['text'=>'Melbourne','correct'=>false],
                            ['text'=>'Canberra','correct'=>true],
                            ['text'=>'Brisbane','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which is the longest river in the world?',
                        'image_url'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/40/Nile_River_and_delta_from_orbit.jpg/240px-Nile_River_and_delta_from_orbit.jpg',
                        'answers'  => [
                            ['text'=>'Amazon','correct'=>false],
                            ['text'=>'Yangtze','correct'=>false],
                            ['text'=>'Mississippi','correct'=>false],
                            ['text'=>'Nile','correct'=>true],
                        ],
                    ],
                    [
                        'text'     => 'On which continent is the Sahara Desert located?',
                        'answers'  => [
                            ['text'=>'Asia','correct'=>false],
                            ['text'=>'Africa','correct'=>true],
                            ['text'=>'South America','correct'=>false],
                            ['text'=>'Australia','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'How many countries are in South America?',
                        'answers'  => [
                            ['text'=>'10','correct'=>false],
                            ['text'=>'12','correct'=>true],
                            ['text'=>'14','correct'=>false],
                            ['text'=>'9','correct'=>false],
                        ],
                        'time_limit'=> 20,
                        'points'   => 1500,
                    ],
                    [
                        'text'     => 'What is the smallest country in the world?',
                        'image_url'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/00/Flag_of_Vatican_City.svg/320px-Flag_of_Vatican_City.svg.png',
                        'answers'  => [
                            ['text'=>'Monaco','correct'=>false],
                            ['text'=>'San Marino','correct'=>false],
                            ['text'=>'Vatican City','correct'=>true],
                            ['text'=>'Liechtenstein','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which mountain is the tallest in the world?',
                        'answers'  => [
                            ['text'=>'K2','correct'=>false],
                            ['text'=>'Kangchenjunga','correct'=>false],
                            ['text'=>'Mount Everest','correct'=>true],
                            ['text'=>'Lhotse','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'What ocean is the largest?',
                        'answers'  => [
                            ['text'=>'Atlantic','correct'=>false],
                            ['text'=>'Indian','correct'=>false],
                            ['text'=>'Arctic','correct'=>false],
                            ['text'=>'Pacific','correct'=>true],
                        ],
                    ],
                    [
                        'text'     => 'Select all countries that border France:',
                        'multiple_correct' => true,
                        'answers'  => [
                            ['text'=>'Spain','correct'=>true],
                            ['text'=>'Portugal','correct'=>false],
                            ['text'=>'Germany','correct'=>true],
                            ['text'=>'Italy','correct'=>true],
                        ],
                        'time_limit'=> 30,
                    ],
                    [
                        'text'     => 'What is the capital of Japan?',
                        'answers'  => [
                            ['text'=>'Osaka','correct'=>false],
                            ['text'=>'Kyoto','correct'=>false],
                            ['text'=>'Tokyo','correct'=>true],
                            ['text'=>'Hiroshima','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which country has the most natural lakes?',
                        'answers'  => [
                            ['text'=>'Russia','correct'=>false],
                            ['text'=>'USA','correct'=>false],
                            ['text'=>'Finland','correct'=>false],
                            ['text'=>'Canada','correct'=>true],
                        ],
                    ],
                ],
            ],

            // ─────────────────────────────────────────────
            // 3. POP CULTURE & ENTERTAINMENT
            // ─────────────────────────────────────────────
            [
                'title'       => 'Pop Culture Trivia',
                'description' => 'Movies, music, TV shows, and internet moments.',
                'category'    => 'Entertainment',
                'tags'        => 'movies,music,tv,pop culture',
                'questions'   => [
                    [
                        'text'     => 'Which Disney movie features the song "Let It Go"?',
                        'image_url'=> 'https://upload.wikimedia.org/wikipedia/en/0/05/Frozen_%282013_film%29_poster.jpg',
                        'answers'  => [
                            ['text'=>'Tangled','correct'=>false],
                            ['text'=>'Brave','correct'=>false],
                            ['text'=>'Moana','correct'=>false],
                            ['text'=>'Frozen','correct'=>true],
                        ],
                    ],
                    [
                        'text'     => 'How many seasons does the TV show "Breaking Bad" have?',
                        'answers'  => [
                            ['text'=>'4','correct'=>false],
                            ['text'=>'5','correct'=>true],
                            ['text'=>'6','correct'=>false],
                            ['text'=>'3','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which artist released the album "Thriller"?',
                        'answers'  => [
                            ['text'=>'Prince','correct'=>false],
                            ['text'=>'Michael Jackson','correct'=>true],
                            ['text'=>'Madonna','correct'=>false],
                            ['text'=>'David Bowie','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'What year was the first iPhone released?',
                        'answers'  => [
                            ['text'=>'2005','correct'=>false],
                            ['text'=>'2006','correct'=>false],
                            ['text'=>'2007','correct'=>true],
                            ['text'=>'2008','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which of these are Marvel Avengers? (select all)',
                        'multiple_correct' => true,
                        'answers'  => [
                            ['text'=>'Iron Man','correct'=>true],
                            ['text'=>'Batman','correct'=>false],
                            ['text'=>'Thor','correct'=>true],
                            ['text'=>'Black Widow','correct'=>true],
                        ],
                        'time_limit'=> 25,
                    ],
                    [
                        'text'     => 'Who played Jack in "Titanic" (1997)?',
                        'image_url'=> 'https://upload.wikimedia.org/wikipedia/en/1/18/Titanic_%281997_film%29_poster.png',
                        'answers'  => [
                            ['text'=>'Brad Pitt','correct'=>false],
                            ['text'=>'Tom Cruise','correct'=>false],
                            ['text'=>'Leonardo DiCaprio','correct'=>true],
                            ['text'=>'Johnny Depp','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'What is the highest-grossing film of all time (unadjusted)?',
                        'answers'  => [
                            ['text'=>'Avengers: Endgame','correct'=>false],
                            ['text'=>'Titanic','correct'=>false],
                            ['text'=>'Avatar','correct'=>true],
                            ['text'=>'Star Wars: The Force Awakens','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'In "The Office" (US), what is the name of the paper company?',
                        'answers'  => [
                            ['text'=>'Dunder Mifflin','correct'=>true],
                            ['text'=>'Paper Kings','correct'=>false],
                            ['text'=>'Scranton Supply Co.','correct'=>false],
                            ['text'=>'Wernham Hogg','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which band sang "Bohemian Rhapsody"?',
                        'answers'  => [
                            ['text'=>'Led Zeppelin','correct'=>false],
                            ['text'=>'The Beatles','correct'=>false],
                            ['text'=>'Queen','correct'=>true],
                            ['text'=>'Aerosmith','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'What streaming platform is "Stranger Things" on?',
                        'answers'  => [
                            ['text'=>'HBO Max','correct'=>false],
                            ['text'=>'Disney+','correct'=>false],
                            ['text'=>'Netflix','correct'=>true],
                            ['text'=>'Amazon Prime','correct'=>false],
                        ],
                    ],
                ],
            ],

            // ─────────────────────────────────────────────
            // 4. HISTORY
            // ─────────────────────────────────────────────
            [
                'title'       => 'History Through the Ages',
                'description' => 'Ancient empires to modern events — how well do you know the past?',
                'category'    => 'History',
                'tags'        => 'history,ancient,wars,world events',
                'questions'   => [
                    [
                        'text'     => 'In which year did World War II end?',
                        'answers'  => [
                            ['text'=>'1943','correct'=>false],
                            ['text'=>'1944','correct'=>false],
                            ['text'=>'1945','correct'=>true],
                            ['text'=>'1946','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Who was the first US President?',
                        'image_url'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b6/Gilbert_Stuart_Williamstown_Portrait_of_George_Washington.jpg/240px-Gilbert_Stuart_Williamstown_Portrait_of_George_Washington.jpg',
                        'answers'  => [
                            ['text'=>'Thomas Jefferson','correct'=>false],
                            ['text'=>'John Adams','correct'=>false],
                            ['text'=>'Abraham Lincoln','correct'=>false],
                            ['text'=>'George Washington','correct'=>true],
                        ],
                    ],
                    [
                        'text'     => 'The Great Wall of China was primarily built to defend against which group?',
                        'answers'  => [
                            ['text'=>'Romans','correct'=>false],
                            ['text'=>'Mongols','correct'=>true],
                            ['text'=>'Persians','correct'=>false],
                            ['text'=>'Japanese','correct'=>false],
                        ],
                        'time_limit'=> 20,
                    ],
                    [
                        'text'     => 'What event started World War I?',
                        'answers'  => [
                            ['text'=>'Invasion of Poland','correct'=>false],
                            ['text'=>'Sinking of the Lusitania','correct'=>false],
                            ['text'=>'Russian Revolution','correct'=>false],
                            ['text'=>'Assassination of Archduke Franz Ferdinand','correct'=>true],
                        ],
                        'time_limit'=> 25,
                        'points'   => 1500,
                    ],
                    [
                        'text'     => 'Which ancient wonder was located in Alexandria, Egypt?',
                        'answers'  => [
                            ['text'=>'Hanging Gardens','correct'=>false],
                            ['text'=>'Colossus of Rhodes','correct'=>false],
                            ['text'=>'The Great Lighthouse','correct'=>true],
                            ['text'=>'Temple of Artemis','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Who was the first person to walk on the Moon?',
                        'video_url'=> 'https://www.youtube.com/watch?v=S9HdPi9Ikhk',
                        'answers'  => [
                            ['text'=>'Buzz Aldrin','correct'=>false],
                            ['text'=>'Yuri Gagarin','correct'=>false],
                            ['text'=>'Neil Armstrong','correct'=>true],
                            ['text'=>'Michael Collins','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'The Roman Colosseum is located in which modern city?',
                        'image_url'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/de/Colosseo_2020.jpg/320px-Colosseo_2020.jpg',
                        'answers'  => [
                            ['text'=>'Athens','correct'=>false],
                            ['text'=>'Rome','correct'=>true],
                            ['text'=>'Istanbul','correct'=>false],
                            ['text'=>'Cairo','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'In what year did the Berlin Wall fall?',
                        'answers'  => [
                            ['text'=>'1987','correct'=>false],
                            ['text'=>'1991','correct'=>false],
                            ['text'=>'1989','correct'=>true],
                            ['text'=>'1985','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which of these empires were at some point the largest in history? (select all)',
                        'multiple_correct' => true,
                        'answers'  => [
                            ['text'=>'British Empire','correct'=>true],
                            ['text'=>'Mongol Empire','correct'=>true],
                            ['text'=>'Greek City-States','correct'=>false],
                            ['text'=>'Roman Empire','correct'=>false],
                        ],
                        'time_limit'=> 30,
                        'points'   => 2000,
                    ],
                    [
                        'text'     => 'What year did Columbus first reach the Americas?',
                        'answers'  => [
                            ['text'=>'1488','correct'=>false],
                            ['text'=>'1492','correct'=>true],
                            ['text'=>'1500','correct'=>false],
                            ['text'=>'1510','correct'=>false],
                        ],
                    ],
                ],
            ],

            // ─────────────────────────────────────────────
            // 5. SPORTS
            // ─────────────────────────────────────────────
            [
                'title'       => 'Sports Trivia Blitz',
                'description' => 'Football, basketball, soccer, Olympics and more!',
                'category'    => 'Sports',
                'tags'        => 'sports,football,basketball,soccer,olympics',
                'questions'   => [
                    [
                        'text'     => 'How many players are on a standard basketball team on the court?',
                        'answers'  => [
                            ['text'=>'4','correct'=>false],
                            ['text'=>'5','correct'=>true],
                            ['text'=>'6','correct'=>false],
                            ['text'=>'7','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which country won the 2018 FIFA World Cup?',
                        'image_url'=> 'https://upload.wikimedia.org/wikipedia/en/thumb/b/b1/2018_FIFA_World_Cup.svg/240px-2018_FIFA_World_Cup.svg.png',
                        'answers'  => [
                            ['text'=>'Brazil','correct'=>false],
                            ['text'=>'Germany','correct'=>false],
                            ['text'=>'Croatia','correct'=>false],
                            ['text'=>'France','correct'=>true],
                        ],
                    ],
                    [
                        'text'     => 'What sport is played at Wimbledon?',
                        'answers'  => [
                            ['text'=>'Cricket','correct'=>false],
                            ['text'=>'Golf','correct'=>false],
                            ['text'=>'Tennis','correct'=>true],
                            ['text'=>'Polo','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'How many rings are on the Olympic flag?',
                        'answers'  => [
                            ['text'=>'4','correct'=>false],
                            ['text'=>'6','correct'=>false],
                            ['text'=>'5','correct'=>true],
                            ['text'=>'7','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which NBA team has won the most championships?',
                        'answers'  => [
                            ['text'=>'Chicago Bulls','correct'=>false],
                            ['text'=>'Golden State Warriors','correct'=>false],
                            ['text'=>'Los Angeles Lakers','correct'=>false],
                            ['text'=>'Boston Celtics','correct'=>true],
                        ],
                        'time_limit'=> 20,
                        'points'   => 1500,
                    ],
                    [
                        'text'     => 'In American Football, how many points is a touchdown worth?',
                        'answers'  => [
                            ['text'=>'3','correct'=>false],
                            ['text'=>'7','correct'=>false],
                            ['text'=>'6','correct'=>true],
                            ['text'=>'4','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'What is the diameter of a basketball hoop in inches?',
                        'answers'  => [
                            ['text'=>'16 inches','correct'=>false],
                            ['text'=>'20 inches','correct'=>false],
                            ['text'=>'18 inches','correct'=>true],
                            ['text'=>'22 inches','correct'=>false],
                        ],
                        'time_limit'=> 25,
                        'points'   => 2000,
                    ],
                    [
                        'text'     => 'Which of these athletes have won more than 4 Olympic gold medals? (select all)',
                        'multiple_correct' => true,
                        'answers'  => [
                            ['text'=>'Michael Phelps','correct'=>true],
                            ['text'=>'Usain Bolt','correct'=>true],
                            ['text'=>'Simone Biles','correct'=>true],
                            ['text'=>'Roger Federer','correct'=>false],
                        ],
                        'time_limit'=> 30,
                    ],
                    [
                        'text'     => 'A "hat trick" in soccer means scoring how many goals in one match?',
                        'answers'  => [
                            ['text'=>'2','correct'=>false],
                            ['text'=>'3','correct'=>true],
                            ['text'=>'4','correct'=>false],
                            ['text'=>'5','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which country invented the sport of baseball?',
                        'answers'  => [
                            ['text'=>'Canada','correct'=>false],
                            ['text'=>'Cuba','correct'=>false],
                            ['text'=>'England','correct'=>false],
                            ['text'=>'United States','correct'=>true],
                        ],
                    ],
                ],
            ],

            // ─────────────────────────────────────────────
            // 6. FOOD & DRINK
            // ─────────────────────────────────────────────
            [
                'title'       => 'Food & Drink Fiesta',
                'description' => 'Cuisine, cocktails, and culinary trivia from around the world.',
                'category'    => 'Food & Drink',
                'tags'        => 'food,drink,cooking,cuisine',
                'questions'   => [
                    [
                        'text'     => 'What is the main ingredient in guacamole?',
                        'image_url'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/13/Guacamole_IMGP1272.jpg/320px-Guacamole_IMGP1272.jpg',
                        'answers'  => [
                            ['text'=>'Mango','correct'=>false],
                            ['text'=>'Avocado','correct'=>true],
                            ['text'=>'Lime','correct'=>false],
                            ['text'=>'Tomato','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which country is sushi originally from?',
                        'answers'  => [
                            ['text'=>'China','correct'=>false],
                            ['text'=>'South Korea','correct'=>false],
                            ['text'=>'Japan','correct'=>true],
                            ['text'=>'Thailand','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'What type of pasta is shaped like small rice grains?',
                        'answers'  => [
                            ['text'=>'Orzo','correct'=>true],
                            ['text'=>'Fusilli','correct'=>false],
                            ['text'=>'Penne','correct'=>false],
                            ['text'=>'Rigatoni','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which of these cheeses are Italian? (select all)',
                        'multiple_correct' => true,
                        'answers'  => [
                            ['text'=>'Parmesan','correct'=>true],
                            ['text'=>'Mozzarella','correct'=>true],
                            ['text'=>'Brie','correct'=>false],
                            ['text'=>'Gouda','correct'=>false],
                        ],
                        'time_limit'=> 25,
                    ],
                    [
                        'text'     => 'How many teaspoons are in a tablespoon?',
                        'answers'  => [
                            ['text'=>'2','correct'=>false],
                            ['text'=>'3','correct'=>true],
                            ['text'=>'4','correct'=>false],
                            ['text'=>'5','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'What is the base spirit in a Margarita?',
                        'answers'  => [
                            ['text'=>'Vodka','correct'=>false],
                            ['text'=>'Rum','correct'=>false],
                            ['text'=>'Gin','correct'=>false],
                            ['text'=>'Tequila','correct'=>true],
                        ],
                    ],
                    [
                        'text'     => 'Croissants are a staple of which country\'s cuisine?',
                        'image_url'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a4/2010-04-29_A_Croissant_on_a_Plate.jpg/320px-2010-04-29_A_Croissant_on_a_Plate.jpg',
                        'answers'  => [
                            ['text'=>'France','correct'=>true],
                            ['text'=>'Belgium','correct'=>false],
                            ['text'=>'Austria','correct'=>false],
                            ['text'=>'Germany','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'What is the Scoville scale used to measure?',
                        'answers'  => [
                            ['text'=>'Saltiness','correct'=>false],
                            ['text'=>'Sweetness','correct'=>false],
                            ['text'=>'Spiciness / heat of peppers','correct'=>true],
                            ['text'=>'Acidity','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which nut is used to make marzipan?',
                        'answers'  => [
                            ['text'=>'Walnut','correct'=>false],
                            ['text'=>'Cashew','correct'=>false],
                            ['text'=>'Hazelnut','correct'=>false],
                            ['text'=>'Almond','correct'=>true],
                        ],
                    ],
                    [
                        'text'     => 'What fruit is used to make traditional wine?',
                        'answers'  => [
                            ['text'=>'Apple','correct'=>false],
                            ['text'=>'Grape','correct'=>true],
                            ['text'=>'Plum','correct'=>false],
                            ['text'=>'Cherry','correct'=>false],
                        ],
                    ],
                ],
            ],

            // ─────────────────────────────────────────────
            // 7. TECHNOLOGY
            // ─────────────────────────────────────────────
            [
                'title'       => 'Tech & Computers Quiz',
                'description' => 'From binary to browsers — how tech-savvy are you?',
                'category'    => 'Technology',
                'tags'        => 'technology,computers,internet,coding',
                'questions'   => [
                    [
                        'text'     => 'What does "CPU" stand for?',
                        'answers'  => [
                            ['text'=>'Central Processing Unit','correct'=>true],
                            ['text'=>'Computer Power Unit','correct'=>false],
                            ['text'=>'Core Processing Utility','correct'=>false],
                            ['text'=>'Central Program Uplink','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which company created the Python programming language?',
                        'answers'  => [
                            ['text'=>'Microsoft','correct'=>false],
                            ['text'=>'Sun Microsystems','correct'=>false],
                            ['text'=>'Guido van Rossum (independent)','correct'=>true],
                            ['text'=>'Google','correct'=>false],
                        ],
                        'time_limit'=> 25,
                        'points'   => 1500,
                    ],
                    [
                        'text'     => 'What does "HTML" stand for?',
                        'answers'  => [
                            ['text'=>'HyperText Markup Language','correct'=>true],
                            ['text'=>'High Transfer Markup Logic','correct'=>false],
                            ['text'=>'Hyper Transfer Module List','correct'=>false],
                            ['text'=>'HyperText Module Language','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'What is the binary representation of the decimal number 5?',
                        'answers'  => [
                            ['text'=>'101','correct'=>true],
                            ['text'=>'110','correct'=>false],
                            ['text'=>'011','correct'=>false],
                            ['text'=>'100','correct'=>false],
                        ],
                        'time_limit'=> 20,
                        'points'   => 2000,
                    ],
                    [
                        'text'     => 'Which of these are programming languages? (select all)',
                        'multiple_correct' => true,
                        'answers'  => [
                            ['text'=>'Rust','correct'=>true],
                            ['text'=>'Kotlin','correct'=>true],
                            ['text'=>'Oracle','correct'=>false],
                            ['text'=>'Swift','correct'=>true],
                        ],
                        'time_limit'=> 25,
                    ],
                    [
                        'text'     => 'In what year was the World Wide Web invented by Tim Berners-Lee?',
                        'answers'  => [
                            ['text'=>'1985','correct'=>false],
                            ['text'=>'1991','correct'=>false],
                            ['text'=>'1989','correct'=>true],
                            ['text'=>'1995','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'What does "RAM" stand for?',
                        'answers'  => [
                            ['text'=>'Random Access Memory','correct'=>true],
                            ['text'=>'Read And Manage','correct'=>false],
                            ['text'=>'Rapid Access Module','correct'=>false],
                            ['text'=>'Rotational Array Memory','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which company makes the Android operating system?',
                        'image_url'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d7/Android_robot.svg/180px-Android_robot.svg.png',
                        'answers'  => [
                            ['text'=>'Apple','correct'=>false],
                            ['text'=>'Samsung','correct'=>false],
                            ['text'=>'Google','correct'=>true],
                            ['text'=>'Microsoft','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'What symbol is used to comment a single line in Python?',
                        'answers'  => [
                            ['text'=>'//','correct'=>false],
                            ['text'=>'#','correct'=>true],
                            ['text'=>'--','correct'=>false],
                            ['text'=>'/*','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'What does "URL" stand for?',
                        'answers'  => [
                            ['text'=>'Universal Resource Listing','correct'=>false],
                            ['text'=>'Uniform Resource Locator','correct'=>true],
                            ['text'=>'Unified Remote Link','correct'=>false],
                            ['text'=>'Uniform Record Link','correct'=>false],
                        ],
                    ],
                ],
            ],

            // ─────────────────────────────────────────────
            // 8. MUSIC
            // ─────────────────────────────────────────────
            [
                'title'       => 'Music Mania',
                'description' => 'Artists, albums, lyrics and music history.',
                'category'    => 'Music',
                'tags'        => 'music,artists,albums,pop,rock',
                'questions'   => [
                    [
                        'text'     => 'How many strings does a standard guitar have?',
                        'answers'  => [
                            ['text'=>'4','correct'=>false],
                            ['text'=>'5','correct'=>false],
                            ['text'=>'6','correct'=>true],
                            ['text'=>'7','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which artist is known as the "Queen of Pop"?',
                        'image_url'=> 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a0/Madonna_%28Vogue%29.png/240px-Madonna_%28Vogue%29.png',
                        'answers'  => [
                            ['text'=>'Beyoncé','correct'=>false],
                            ['text'=>'Madonna','correct'=>true],
                            ['text'=>'Rihanna','correct'=>false],
                            ['text'=>'Lady Gaga','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'What is the best-selling album of all time?',
                        'answers'  => [
                            ['text'=>'Back in Black – AC/DC','correct'=>false],
                            ['text'=>'The Dark Side of the Moon – Pink Floyd','correct'=>false],
                            ['text'=>'Thriller – Michael Jackson','correct'=>true],
                            ['text'=>'Eagles – Their Greatest Hits','correct'=>false],
                        ],
                        'time_limit'=> 25,
                        'points'   => 1500,
                    ],
                    [
                        'text'     => 'Which instrument has 88 keys?',
                        'answers'  => [
                            ['text'=>'Organ','correct'=>false],
                            ['text'=>'Piano','correct'=>true],
                            ['text'=>'Harpsichord','correct'=>false],
                            ['text'=>'Accordion','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which of these are Beatles members? (select all)',
                        'multiple_correct' => true,
                        'answers'  => [
                            ['text'=>'John Lennon','correct'=>true],
                            ['text'=>'Mick Jagger','correct'=>false],
                            ['text'=>'Paul McCartney','correct'=>true],
                            ['text'=>'Ringo Starr','correct'=>true],
                        ],
                        'time_limit'=> 25,
                    ],
                    [
                        'text'     => 'What genre of music is associated with artists like Tupac and Biggie?',
                        'answers'  => [
                            ['text'=>'R&B','correct'=>false],
                            ['text'=>'Hip-Hop / Rap','correct'=>true],
                            ['text'=>'Reggae','correct'=>false],
                            ['text'=>'Soul','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'In music, what does "BPM" stand for?',
                        'answers'  => [
                            ['text'=>'Bass Per Mix','correct'=>false],
                            ['text'=>'Beats Per Minute','correct'=>true],
                            ['text'=>'Beat Phase Meter','correct'=>false],
                            ['text'=>'Binary Pulse Measurement','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which country does K-pop originate from?',
                        'answers'  => [
                            ['text'=>'Japan','correct'=>false],
                            ['text'=>'China','correct'=>false],
                            ['text'=>'South Korea','correct'=>true],
                            ['text'=>'Thailand','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'What Adele song starts with "Hello, it\'s me"?',
                        'answers'  => [
                            ['text'=>'Someone Like You','correct'=>false],
                            ['text'=>'Rolling in the Deep','correct'=>false],
                            ['text'=>'Hello','correct'=>true],
                            ['text'=>'Skyfall','correct'=>false],
                        ],
                    ],
                    [
                        'text'     => 'Which decade is known as the birth of rock and roll?',
                        'answers'  => [
                            ['text'=>'1940s','correct'=>false],
                            ['text'=>'1960s','correct'=>false],
                            ['text'=>'1970s','correct'=>false],
                            ['text'=>'1950s','correct'=>true],
                        ],
                    ],
                ],
            ],

        ];
    }
}
