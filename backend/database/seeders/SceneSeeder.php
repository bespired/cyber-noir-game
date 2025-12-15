<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Scene;

class SceneSeeder extends Seeder
{
    use LoadsJsonData;

    public function run(): void
    {
        if ($this->loadJson('scenes', Scene::class)) {
            return;
        }
    }
}
