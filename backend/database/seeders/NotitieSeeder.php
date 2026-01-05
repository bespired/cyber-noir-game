<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notitie;

class NotitieSeeder extends Seeder
{
    use LoadsJsonData;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if ($this->loadJson('notities', Notitie::class)) {
            return;
        }

        Notitie::create([
            'titel' => 'Verdacht Gedrag',
            'inhoud' => 'De barman gedroeg zich vreemd toen ik vroeg naar de replicant.',
            'is_afgerond' => false,
        ]);

        Notitie::create([
            'titel' => 'Locatie Check',
            'inhoud' => 'Sector 7 moet nogmaals onderzocht worden.',
            'is_afgerond' => true,
        ]);

        Notitie::factory(5)->create();
    }
}
