<?php
namespace Database\Seeders;

use App\Models\ScenePersonage;
use Illuminate\Database\Seeder;

class ScenePersonageSeeder extends Seeder
{
    use LoadsJsonData;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if ($this->loadJson('scene_personages', ScenePersonage::class)) {
            return;
        }
    }
}
