<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ArtworkInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:artwork-install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install initial assets (artwork and glb) from Google Drive (no credentials required)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Priority: Config (Env/File) -> Database -> Env Fallback
        $fileId = config('install.asset_install_id');

        if (!$fileId) {
            // Fallback to database for backward compatibility
            $fileId = \App\Models\Instelling::getWaarde('asset_install_id', env('GOOGLE_DRIVE_INITIAL_ASSETS_ID'));
        }

        if (!$fileId) {
            $this->error('Asset Install ID not found.');
            $this->error('Please ensure "asset_install_id" is set in config/install.php, .env, or the "instellingen" table');
            return Command::FAILURE;
        }

        $this->info("Starting Asset Install...");
        $this->info("Fetching file from Google Drive (ID: {$fileId})...");

        // Google Drive direct download link
        $url = "https://drive.google.com/uc?export=download&id={$fileId}&confirm=t";

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(600)->get($url);

            if (!$response->successful()) {
                $this->error('Failed to download from Google Drive.');
                $this->error('Status Code: ' . $response->status());
                return Command::FAILURE;
            }

            $zipContent = $response->body();

            // If Google Drive returns an HTML page, it's likely a "large file" warning
            if (str_contains($zipContent, '<!DOCTYPE html>') || str_contains($zipContent, '<html>')) {
                $this->info("Large file detected, fetching confirmation token and UUID...");

                // Try to find the tokens in hidden inputs
                preg_match('/name="confirm" value="([^"]+)"/', $zipContent, $confirmMatches);
                $confirmToken = $confirmMatches[1] ?? null;

                preg_match('/name="uuid" value="([^"]+)"/', $zipContent, $uuidMatches);
                $uuidToken = $uuidMatches[1] ?? null;

                if (!$confirmToken) {
                    // Fallback for older pattern if hidden input not found
                    if (preg_match('/confirm=([0-9A-Za-z_]+)/', $zipContent, $matches)) {
                        $confirmToken = $matches[1];
                    }
                }

                if ($confirmToken) {
                    $this->info("Token found: {$confirmToken}");
                    if ($uuidToken) {
                        $this->info("UUID found: {$uuidToken}");
                    }
                    $this->info("Retrying download from drive.usercontent.google.com...");

                    // Construct the retry URL with all necessary parameters
                    $retryUrl = "https://drive.usercontent.google.com/download";
                    $queryParams = [
                        'id' => $fileId,
                        'export' => 'download',
                        'confirm' => $confirmToken,
                    ];
                    if ($uuidToken) {
                        $queryParams['uuid'] = $uuidToken;
                    }

                    $response = \Illuminate\Support\Facades\Http::timeout(600)->get($retryUrl, $queryParams);

                    if (!$response->successful()) {
                        $this->error('Failed to download from Google Drive on retry.');
                        $this->error('Status Code: ' . $response->status());
                        return Command::FAILURE;
                    }

                    $zipContent = $response->body();
                } else {
                    $this->error('It seems Google Drive returned a web page instead of a file, and no confirmation token was found.');
                    $this->error('This usually happens if the link is not shared publicly.');
                    $this->line('Try copying the link into a private browser window to verify it downloads directly.');
                    return Command::FAILURE;
                }
            }

            $zipPath = storage_path('app/assets_install_temp.zip');
            file_put_contents($zipPath, $zipContent);

            $this->info("Extracting archive...");
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) === true) {
                $publicPath = storage_path('app/public');
                if (!is_dir($publicPath)) {
                    mkdir($publicPath, 0755, true);
                }

                $zip->extractTo($publicPath);
                $zip->close();
                $this->info("Assets extracted to: {$publicPath}");
            } else {
                $this->error("Failed to open the downloaded zip file.");
                unlink($zipPath);
                return Command::FAILURE;
            }

            unlink($zipPath);
            $this->info('Assets extracted successfully!');

            // Restore Data if backup folder exists
            $backupPath = storage_path('app/public/backup');
            if (is_dir($backupPath)) {
                $this->info('Restoring database from backup...');

                $imports = [
                    'instellingen' => \App\Models\Instelling::class,
                    'personages' => \App\Models\Personage::class,
                    'locaties' => \App\Models\Locatie::class,
                    'aanwijzingen' => \App\Models\Aanwijzing::class,
                    'sectoren' => \App\Models\Sector::class,
                    'scenes' => \App\Models\Scene::class,
                    'notities' => \App\Models\Notitie::class,
                    'dialogen' => \App\Models\Dialoog::class,
                    'afbeeldingen' => \App\Models\Afbeelding::class,
                    'gedragingen' => \App\Models\Gedrag::class,
                    'scene_personages' => \App\Models\ScenePersonage::class,
                ];

                \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

                foreach ($imports as $key => $modelClass) {
                    $jsonPath = "$backupPath/{$key}.json";
                    if (file_exists($jsonPath)) {
                        $this->info("Restoring {$key}...");
                        $data = json_decode(file_get_contents($jsonPath), true);
                        if (is_array($data)) {
                            $modelClass::truncate();
                            // Chunk inserts or loop
                            foreach ($data as $item) {
                                $modelClass::create($item);
                            }
                            $this->line("Restored " . count($data) . " records for {$key}.");
                        }
                    } else {
                        $this->warn("No backup file found for {$key}");
                    }
                }

                \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
                $this->info('Database restoration complete!');
            }

            $this->info('Installation successful!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
