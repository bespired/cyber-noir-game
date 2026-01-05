<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sector;

class SectorSeeder extends Seeder
{
    use LoadsJsonData;

    public function run(): void
    {
        // Load from 'sectoren.json' (mapped from 'sectoren' key in export)
        if ($this->loadJson('sectoren', Sector::class)) {
            return;
        }
    }
}
