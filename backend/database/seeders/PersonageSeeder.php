<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Personage;

class PersonageSeeder extends Seeder
{
    use LoadsJsonData;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Try loading from JSON backup first
        if ($this->loadJson('personages', Personage::class)) {
            return;
        }

        // Fallback: Create default data if no JSON found
        Personage::factory(10)->create();
    }
}
