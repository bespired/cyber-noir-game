<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Note;

class NoteSeeder extends Seeder
{
    use LoadsJsonData;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if ($this->loadJson('notes', Note::class)) {
            return;
        }

        Note::create([
            'titel' => 'Verdacht Gedrag',
            'inhoud' => 'De barman gedroeg zich vreemd toen ik vroeg naar de replicant.',
            'is_afgerond' => false,
        ]);

        Note::create([
            'titel' => 'Locatie Check',
            'inhoud' => 'Sector 7 moet nogmaals onderzocht worden.',
            'is_afgerond' => true,
        ]);

        Note::factory(5)->create();
    }
}
