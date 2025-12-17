<?php
namespace Database\Seeders;

use App\Models\Aanwijzing;
use Illuminate\Database\Seeder;

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

    }
}
