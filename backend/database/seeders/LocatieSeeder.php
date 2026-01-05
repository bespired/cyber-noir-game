<?php
namespace Database\Seeders;

use App\Models\Locatie;
use Illuminate\Database\Seeder;

class LocatieSeeder extends Seeder
{
    use LoadsJsonData;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if ($this->loadJson('locaties', Locatie::class)) {
            return;
        }

        // Locatie::factory(5)->create();
    }
}
