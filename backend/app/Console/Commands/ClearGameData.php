<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearGameData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear exported game data, assets, and synced components from the Electron folder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing exported game data...');

        $electronPath = File::exists('/electron') ? '/electron' : base_path('../electron');

        if (!File::exists($electronPath)) {
            $this->error("Electron project not found at: $electronPath");
            return 1;
        }

        // 1. Clear Public Assets and Data
        $publicPath = $electronPath . '/public';
        $toClearPublic = [
            $publicPath . '/data',
            $publicPath . '/assets',
            $publicPath . '/font',
            $publicPath . '/game.css',
        ];

        foreach ($toClearPublic as $path) {
            if (File::exists($path)) {
                if (File::isDirectory($path)) {
                    File::deleteDirectory($path);
                    $this->line("Deleted directory: " . basename($path));
                } else {
                    File::delete($path);
                    $this->line("Deleted file: " . basename($path));
                }
            }
        }

        // 2. Clear Source Data and Synced Components
        $srcPath = $electronPath . '/src';
        $toClearSrc = [
            $srcPath . '/sectordata.js',
            $srcPath . '/dialogs.js',
            $srcPath . '/settings.js',
            $srcPath . '/data/dialogs.js',
            $srcPath . '/components/game_scenes',
            $srcPath . '/components/inputs',
            $srcPath . '/composables',
            $srcPath . '/services',
            $srcPath . '/axios.js',
        ];

        foreach ($toClearSrc as $path) {
            if (File::exists($path)) {
                if (File::isDirectory($path)) {
                    // We delete the directory itself because game:export will recreate it
                    File::deleteDirectory($path);
                    $this->line("Deleted directory: " . basename($path));
                } else {
                    File::delete($path);
                    $this->line("Deleted file: " . basename($path));
                }
            }
        }

        // 3. Clear Build Artifacts
        $distPath = $electronPath . '/dist';
        if (File::exists($distPath)) {
            File::deleteDirectory($distPath);
            $this->line("Deleted directory: dist");
        }

        $this->info('Cleanup complete. The Electron folder is now in a clean state.');
        return 0;
    }
}
