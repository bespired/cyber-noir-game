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
    protected $description = 'Install initial artwork assets from Google Drive (no credentials required)';

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

        $this->info("Starting Artwork Install...");
        $this->info("Fetching file from Google Drive (ID: {$fileId})...");

        // Google Drive direct download link
        // Note: For very large files, Google might require a confirm token.
        // confirm=t works for many cases to skip the scan warning.
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

            // Basic check if we got HTML instead of ZIP (Google sometimes returns warning pages)
            if (str_contains($zipContent, '<!DOCTYPE html>') || str_contains($zipContent, '<html>')) {
                $this->error('It seems Google Drive returned a web page instead of a file.');
                $this->error('This usually happens for very large files or if the link is not shared publicly.');
                $this->line('Try copying the link into a private browser window to verify it downloads directly.');
                return Command::FAILURE;
            }

            $zipPath = storage_path('app/artwork_install_temp.zip');
            file_put_contents($zipPath, $zipContent);

            $this->info("Extracting archive...");
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) === true) {
                $artworkPath = storage_path('app/public/artwork');
                if (!is_dir($artworkPath)) {
                    mkdir($artworkPath, 0755, true);
                }

                $zip->extractTo($artworkPath);
                $zip->close();
                $this->info("Artwork extracted to: {$artworkPath}");
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
