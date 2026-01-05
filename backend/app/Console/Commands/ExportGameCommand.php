<?php
namespace App\Console\Commands;

use App\Models\Aanwijzing;
use App\Models\Dialoog;
use App\Models\Gedrag;
use App\Models\Instelling;
use App\Models\Locatie;
use App\Models\Personage;
use App\Models\Scene;
use App\Models\Sector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;


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
        $srcPath = $electronPath . '/src';
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
        if (!File::exists($srcPath)) {
            File::makeDirectory($srcPath, 0755, true);
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
        $this->exportJson($dataPath, 'scenes.json', Scene::with(['locatie.artwork', 'artwork', 'scenePersonages.personage.artwork', 'scenePersonages.dialoog'])->get()->map(function ($scene) {
            $data = $scene->toArray();
            // Rename locatie to location for frontend compatibility
            $data['location'] = $data['locatie'] ?? null;
            return $data;
        }));
        $this->exportJson($dataPath, 'personages.json', Personage::with('artwork')->get());
        $this->exportJson($dataPath, 'items.json', Aanwijzing::all());
        $this->exportJson($dataPath, 'dialogues.json', Dialoog::all());
        $this->exportJson($dataPath, 'gedrag.json', Gedrag::all());

        // 3.2 Export Sector Data (Unified Game Data)
        $this->info('Exporting sectordata.json...');
        $sectorData = Sector::with([
            'artwork',
            'scenes.locatie.artwork',
            'scenes.artwork',
            'scenes.scenePersonages.personage.artwork',
            'scenes.scenePersonages.dialoog'
        ])->get()->map(function ($sector) {
            return [
                'id' => $sector->id,
                'naam' => $sector->naam,
                'beschrijving' => $sector->beschrijving,
                'ontdek_condities' => null, // Placeholder as per example
                'x' => $sector->x,
                'y' => $sector->y,
                'width' => $sector->width,
                'height' => $sector->height,
                'artwork' => $sector->artwork,
                'scenes' => $sector->scenes->map(function ($scene) use ($sector) {
                    $location = null;
                    if ($scene->locatie) {
                        $locName = $scene->locatie->naam;
                        $sectorName = $sector->naam;
                        $threefile = Str::slug($sectorName) . '--' . Str::slug($locName) . '.glb';

                        $location = [
                            'id' => $scene->locatie->id,
                            'naam' => $locName,
                            'threefile' => $threefile,
                            'beschrijving' => $scene->locatie->beschrijving,
                            'notities' => $scene->locatie->notities,
                            'adres' => $scene->locatie->adres ?? null,
                            'veiligheidsniveau' => $scene->locatie->veiligheidsniveau ?? 1,
                            'spawn_points' => $scene->locatie->spawn_points,
                            'artwork' => $scene->locatie->artwork,
                        ];
                    }

                    return [
                        'id' => $scene->id,
                        'locatie_id' => $scene->locatie_id,
                        'sector_id' => $scene->sector_id,
                        'titel' => $scene->titel,
                        'type' => $scene->type,
                        'beschrijving' => $scene->beschrijving,
                        'status' => $scene->status,
                        'gateways' => $scene->gateways,
                        'data' => $scene->data,
                        'artwork' => $scene->artwork,
                        'npc' => $scene->scenePersonages->map(function ($sp) {
                            $p = $sp->personage;
                            return [
                                'id' => $p->id,
                                'type' => $p->type,
                                'naam' => $p->naam,
                                'rol' => $p->rol,
                                'beschrijving' => $p->beschrijving,
                                'menselijke_status' => $p->menselijke_status,
                                'replicant_status' => $p->replicant_status,
                                'motief' => $p->motief,
                                'is_replicant' => $p->is_replicant,
                                'is_playable' => $p->is_playable,
                                'created_at' => $p->created_at,
                                'updated_at' => $p->updated_at,
                                'artwork' => $p->artwork,
                                'threefile' => Str::slug($p->naam) . '.glb',
                            ];
                        }),
                        'gedrag' => Gedrag::whereIn('id', collect($scene->gateways)->pluck('gedrag_id')->filter()->unique())->get(),
                        'dialogs' => $scene->scenePersonages->pluck('dialoog_id')->filter()->values(),
                        'location' => $location,
                    ];
                }),
            ];
        });

        // $this->exportJson($dataPath, 'sectordata.json', $sectorData);
        $this->exportJs($srcPath, 'sectordata.js', 'sectorData', $sectorData);

        // 3.3 Export Dialogues (Unified Game Data)
        $this->info('Exporting dialogs.js...');
        $dialogs = Dialoog::with('personage')->get();
        $this->exportJs($srcPath . '/data', 'dialogs.js', 'dialogs', $dialogs);
        $this->exportJs($srcPath, 'dialogs.js', 'dialogs', $dialogs); // Also in root src for easy import as requested


        // Global Settings
        $settings = Instelling::pluck('waarde', 'sleutel');
        $this->exportJson($dataPath, 'settings.json', $settings);
        $this->exportJs($srcPath, 'settings.js', 'settings', $settings);

        // 3.1 Export Dynamic CSS
        if (isset($settings['game_css'])) {
            $this->info('Exporting game.css...');
            $css = $settings['game_css'];
            // Fix absolute font paths for Electron file:// protocol
            $css = str_replace("url('/font/", "url('./font/", $css);
            $css = str_replace('url("/font/', 'url("./font/', $css);

            File::put($publicPath . '/game.css', $css);
            $this->line("exported: game.css (fixed font paths)");
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

        // 5.1 Export Input Components
        $this->info('Exporting Input Components...');
        $inputSourcePath = $frontendPath . '/src/components/inputs';
        $inputDestPath = $electronPath . '/src/components/inputs';

        if (!File::exists($inputDestPath)) {
            File::makeDirectory($inputDestPath, 0755, true);
        }

        if (File::exists($inputSourcePath)) {
            File::copyDirectory($inputSourcePath, $inputDestPath);
            $this->info("Input components exported to $inputDestPath");
        } else {
            $this->warn("Input components directory not found: $inputSourcePath");
        }

        // 5.2 Export Composables
        $this->info('Exporting Composables...');
        $compSourcePath = $frontendPath . '/src/composables';
        $compDestPath = $electronPath . '/src/composables';

        if (!File::exists($compDestPath)) {
            File::makeDirectory($compDestPath, 0755, true);
        }

        if (File::exists($compSourcePath)) {
            File::copyDirectory($compSourcePath, $compDestPath);
            $this->info("Composables exported to $compDestPath");
        } else {
            $this->warn("Composables directory not found: $compSourcePath");
        }

        // 5.3 Export Core JS/Vue files
        $this->info('Exporting core engine files (axios, etc.)...');
        $coreFiles = ['src/axios.js'];
        foreach ($coreFiles as $file) {
            $srcFile = $frontendPath . '/' . $file;
            $dstFile = $electronPath . '/' . $file;
            if (File::exists($srcFile)) {
                File::copy($srcFile, $dstFile);
                $this->line("Synced core file: $file");
            }
        }

        // Add App.vue and main.js only if missing (initial setup)
        foreach (['src/App.vue', 'src/main.js'] as $file) {
            $dstFile = $electronPath . '/' . $file;
            if (!File::exists($dstFile)) {
                $srcFile = $frontendPath . '/' . $file;
                if (File::exists($srcFile)) {
                    File::copy($srcFile, $dstFile);
                    $this->line("Initialized missing engine file: $file");
                }
            }
        }

        // 5.4 Export Services
        $this->info('Exporting Services...');
        $serviceSourcePath = $frontendPath . '/src/services';
        $serviceDestPath = $electronPath . '/src/services';
        if (File::exists($serviceSourcePath)) {
            File::copyDirectory($serviceSourcePath, $serviceDestPath);
            $this->info("Services exported to $serviceDestPath");
        }

        // 6. Build Electron App
        $this->info('Building Electron App (Vite)...');
        // Ensure node_modules exists
        if (!File::exists($electronPath . '/node_modules')) {
            $this->info('Installing dependencies...');
            $installOutput = shell_exec("cd $electronPath && npm install 2>&1");
            $this->line($installOutput);
        }

        // Run build
        $output = shell_exec("cd $electronPath && npm run build 2>&1");

        // Handle platform mismatch (common with Docker shared volumes)
        if (str_contains($output, 'MODULE_NOT_FOUND') && (str_contains($output, 'rollup-linux') || str_contains($output, 'vite'))) {
            $this->warn("Detected platform mismatch for Rollup/Vite. Attempting to install missing native binaries...");
            $this->line(shell_exec("cd $electronPath && npm install 2>&1"));
            // Retry build
            $this->info("Retrying build...");
            $output = shell_exec("cd $electronPath && npm run build 2>&1");
        }

        $this->line($output);

        if (str_contains($output, 'built in') || str_contains($output, '✓ built in')) {
            $this->info('Electron build completed successfully.');

            // if ($this->option('dist-only')) {
            //     $this->info('Cleaning up source folders (dist-only mode)...');
            //     File::deleteDirectory($electronPath . '/src');
            //     File::deleteDirectory($electronPath . '/public');
            //     File::deleteDirectory($electronPath . '/node_modules');
            //     File::delete($electronPath . '/vite.config.js');
            //     $this->info('Source folders and node_modules removed. Ready for distribution.');
            // }
        } else {
            $this->error('Electron build might have failed. Check output above.');
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

    private function exportJs($path, $filename, $variableName, $data)
    {
        $fullPath = $path . '/' . $filename;
        $json = $data->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $content = "export const {$variableName} = {$json};\n";
        File::put($fullPath, $content);
        $this->line("exported: $filename (JS Module)");
    }
}
