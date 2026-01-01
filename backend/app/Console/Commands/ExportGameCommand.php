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
        $frontendPath = File::exists('/frontend') ? '/frontend' : base_path('../frontend');

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

        // 3.1 Export Dynamic CSS
        if (isset($settings['game_css'])) {
            $this->info('Exporting game.css...');
            File::put($publicPath . '/game.css', $settings['game_css']);
            $this->line("exported: game.css");
        }

        // 4. Export Fonts
        $this->info('Exporting fonts...');
        $fontSourcePath = $frontendPath . '/public/font';
        $fontDestPath = $publicPath . '/font';

        if (!File::exists($fontDestPath)) {
            File::makeDirectory($fontDestPath, 0755, true);
        }

        // Get fonts from settings
        $fontSetting = $settings['game_fonts'] ?? '[]';

        // Try to decode as JSON first
        $fonts = json_decode($fontSetting, true);

        // If not valid JSON or result is not an array, try comma fallback (legacy support)
        if (!is_array($fonts)) {
            $fonts = array_filter(array_map('trim', explode(',', $fontSetting)));
        }

        // Also copy specific fonts if found, otherwise copy all TTFs?
        // User said: "the needed fonts are defined in 'game_fonts'... and can be found as ttf"
        // Let's try to copy specific files first.
        if (File::exists($fontSourcePath)) {
            if (!empty($fonts)) {
                foreach ($fonts as $fontName) {
                    // Try exact match or append .ttf
                    $srcFile = $fontSourcePath . '/' . $fontName;
                    if (!File::exists($srcFile) && !str_ends_with($fontName, '.ttf')) {
                        $srcFile .= '.ttf';
                    }

                    if (File::exists($srcFile)) {
                        File::copy($srcFile, $fontDestPath . '/' . basename($srcFile));
                        $this->line("Copied font: " . basename($srcFile));
                    } else {
                        $this->warn("Font file not found: $fontName in $fontSourcePath");
                    }
                }
            } else {
                // Fallback: Copy all standard fonts if no setting? Or just warn?
                $this->warn("No 'game_fonts' setting found. Skipping specific font copy.");
            }
        } else {
            $this->warn("Font source directory not found: $fontSourcePath");
        }

        // 5. Export Vue Components (Game Scenes)
        $this->info('Exporting Vue Game Components...');
        $compSourcePath = $frontendPath . '/src/components/game_scenes';
        $compDestPath = $electronPath . '/src/components/game_scenes';

        if (!File::exists($compDestPath)) {
            File::makeDirectory($compDestPath, 0755, true);
        }

        if (File::exists($compSourcePath)) {
            // Copy all .vue files
            $files = File::files($compSourcePath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'vue') {
                    File::copy($file->getPathname(), $compDestPath . '/' . $file->getFilename());
                    $this->line("Copied component: " . $file->getFilename());
                }
            }
        } else {
            $this->warn("Game Scenes components directory not found: $compSourcePath");
        }

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
