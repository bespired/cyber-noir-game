<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Dialoog;

class DialoogSeeder extends Seeder
{
    use LoadsJsonData;

    public function run(): void
    {
        if ($this->loadJson('dialogen', Dialoog::class)) {
            // If we loaded data, we might still want to add defaults if empty?
            // But loadJson returns true if file exists and is valid JSON.
            // If it was empty array, it returns true but creates nothing.
        }

        if (Dialoog::count() === 0) {
             Dialoog::create([
                'titel' => 'Introductie Barman',
                'personage_id' => 1, // Assumes personage with ID 1 exists
                'is_active' => true,
                'tree' => [
                    'root' => [
                        'text' => 'Wat kan ik voor je inschenken?',
                        'options' => [
                            ['text' => 'Geef me informatie.', 'next' => 'info'],
                            ['text' => 'Gewoon een drankje.', 'next' => 'drink'],
                        ]
                    ],
                    'info' => [
                        'text' => 'Informatie is niet gratis, vriend.',
                        'options' => [
                            ['text' => 'Hier is 50 credits.', 'next' => 'paid'],
                            ['text' => 'Laat maar.', 'next' => 'end'],
                        ]
                    ],
                    'drink' => [
                        'text' => 'Komt eraan.',
                        'options' => [
                            ['text' => 'Bedankt.', 'next' => 'end'],
                        ]
                    ],
                    'paid' => [
                        'text' => 'Er is een hacker in Sector 4.',
                        'options' => [
                            ['text' => 'Interessant.', 'next' => 'end'],
                        ]
                    ],
                    'end' => [
                        'text' => '[Einde gesprek]',
                        'options' => []
                    ]
                ]
            ]);
        }
    }
}
