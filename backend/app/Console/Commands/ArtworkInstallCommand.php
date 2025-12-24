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
        $fileId = env('GOOGLE_DRIVE_INITIAL_ASSETS_ID');

        if (!$fileId) {
            $this->error('GOOGLE_DRIVE_INITIAL_ASSETS_ID is not set in .env');
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
                $this->error('Please ensure the file is shared on Google Drive as "Anyone with the link".');
                return Command::FAILURE;
            }

            $zipContent = $response->body();

            // Basic check if we got HTML instead of ZIP
            if (str_contains($zipContent, '<!DOCTYPE html>') || str_contains($zipContent, '<html>')) {
                $this->error('It seems Google Drive returned a web page instead of a file.');
                $this->error('This usually happens for very large files or if the link is not shared publicly.');
                $this->line('Try copying the link into a private browser window to verify it downloads directly.');
                return Command::FAILURE;
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
            $this->info('Installation successful!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
