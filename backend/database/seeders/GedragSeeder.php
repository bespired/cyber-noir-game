<?php
namespace Database\Seeders;

use App\Models\Gedrag;
use Illuminate\Database\Seeder;

class GedragSeeder extends Seeder
{
    use LoadsJsonData;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if ($this->loadJson('gedragingen', Gedrag::class)) {
            return;
        }
    }
}
