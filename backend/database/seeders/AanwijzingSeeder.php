<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Aanwijzing;

class AanwijzingSeeder extends Seeder
{
    use LoadsJsonData;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if ($this->loadJson('aanwijzingen', Aanwijzing::class)) {
            return;
        }

        if (Aanwijzing::count() === 0) {
            Aanwijzing::factory(20)->create();
        }
    }
}
