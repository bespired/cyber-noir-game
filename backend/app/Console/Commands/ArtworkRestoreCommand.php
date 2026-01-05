<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ArtworkRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:artwork-restore {--initial : Restore from initial assets instead of latest backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore the artwork and glb folders from Google Drive';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isInitial = $this->option('initial');
        $this->info($isInitial ? 'Restoring initial assets...' : 'Restoring latest backup from Google Drive...');

        try {
            $disk = \Illuminate\Support\Facades\Storage::disk('google');

            $fileId = null;
            $zipFileName = 'assets_restore.zip';

            if ($isInitial) {
                $fileId = env('GOOGLE_DRIVE_INITIAL_ASSETS_ID');
                if (!$fileId) {
                    $this->error('GOOGLE_DRIVE_INITIAL_ASSETS_ID not set in .env');
                    return Command::FAILURE;
                }
                $this->info("Downloading initial assets (ID/Path: {$fileId})...");
            } else {
                // List files and find the latest backup
                $files = $disk->listContents('.', false);
                $backups = [];
                foreach ($files as $file) {
                    // Look for assets-backup- or old artwork-backup-
                    if ($file['type'] === 'file' && (str_starts_with($file['path'], 'assets-backup-') || str_starts_with($file['path'], 'artwork-backup-'))) {
                        $backups[] = $file;
                    }
                }

                if (empty($backups)) {
                    $this->error('No backups found on Google Drive.');
                    return Command::FAILURE;
                }

                // Sort by name (which includes timestamp) to get the latest
                usort($backups, function ($a, $b) {
                    return strcmp($b['path'], $a['path']);
                });

                $fileId = $backups[0]['path'];
                $this->info("Found latest backup: {$fileId}");
            }

            $zipContent = $disk->get($fileId);
            if (!$zipContent) {
                $this->error("Failed to download file from Google Drive.");
                return Command::FAILURE;
            }

            $zipFilePath = storage_path('app/' . $zipFileName);
            file_put_contents($zipFilePath, $zipContent);

            $this->info("Extracting archive...");
            $zip = new \ZipArchive();
            if ($zip->open($zipFilePath) === true) {
                // Extract to storage/app/public since the zip now contains folders
                $publicPath = storage_path('app/public');
                if (!is_dir($publicPath)) {
                    mkdir($publicPath, 0755, true);
                }

                $zip->extractTo($publicPath);
                $zip->close();
                $this->info("Extraction successful.");
            } else {
                $this->error("Failed to open zip archive.");
                unlink($zipFilePath);
                return Command::FAILURE;
            }

            $this->info("Cleaning up...");
            unlink($zipFilePath);

        } catch (\Exception $e) {
            $this->error("Error during restore: " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('Restore Complete!');
        return Command::SUCCESS;
    }
}
