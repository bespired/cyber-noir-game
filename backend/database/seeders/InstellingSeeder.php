<?php
namespace Database\Seeders;

use App\Models\Instelling;
use Illuminate\Database\Seeder;

class InstellingSeeder extends Seeder
{
    use LoadsJsonData;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if ($this->loadJson('instellingen', Instelling::class)) {
            return;
        }
    }
}
