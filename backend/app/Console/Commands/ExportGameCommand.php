<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\Locatie;
use App\Models\Personage;
use App\Models\Aanwijzing;
use App\Models\Dialoog;
use App\Models\Sector;
use App\Models\Instelling;

class ExportGameCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export game data and assets to the Electron game engine';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Game Export...');

        // Define Paths
        // Check for Docker volume mount first, then fallback to local relative path
        $electronPath = File::exists('/electron') ? '/electron' : base_path('../electron');

        $publicPath = $electronPath . '/public';
        $dataPath = $publicPath . '/data';
        $assetsPath = $publicPath . '/assets';
        $sourceAssetsPath = storage_path('app/public');

        // Check if Electron project exists
        if (!File::exists($electronPath)) {
            $this->error("Electron project not found at: $electronPath");
            return 1;
        }

        // 1. Prepare Directories
        $this->info('Preparing directories...');
        if (!File::exists($dataPath)) {
            File::makeDirectory($dataPath, 0755, true);
        }
        if (!File::exists($assetsPath)) {
            File::makeDirectory($assetsPath, 0755, true);
        }

        // 2. Export Assets (Copy Images/Audio)
        $this->info('Copying assets...');
        // We use rsync-like behavior: delete target assets and copy fresh to ensure no stale files?
        // Or just copy over. "copyDirectory" overwrites.
        // For safety/cleanliness, let's just copy.
        if (File::exists($sourceAssetsPath)) {
            File::copyDirectory($sourceAssetsPath, $assetsPath);
            $this->info("Assets copied to $assetsPath");
        } else {
            $this->warn("No source assets found at $sourceAssetsPath");
        }

        // 3. Export Data (JSON)
        $this->info('Exporting data...');

        $this->exportJson($dataPath, 'sectors.json', Sector::with('scenes')->get());
        $this->exportJson($dataPath, 'locations.json', Locatie::with('scenes')->get());
        $this->exportJson($dataPath, 'personages.json', Personage::all());
        $this->exportJson($dataPath, 'items.json', Aanwijzing::all());
        $this->exportJson($dataPath, 'dialogues.json', Dialoog::all()); // Depending on structure, might need processing

        // Global Settings
        $settings = Instelling::pluck('waarde', 'sleutel');
        $this->exportJson($dataPath, 'settings.json', $settings);

        $this->info('Game Export Completed Successfully!');
        return 0;
    }

    private function exportJson($path, $filename, $data)
    {
        $fullPath = $path . '/' . $filename;
        File::put($fullPath, $data->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->line("exported: $filename");
    }
}
