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
    protected $description = 'Restore the artwork folder from Google Drive';

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
            $zipFileName = 'artwork_restore.zip';

            if ($isInitial) {
                $fileId = env('GOOGLE_DRIVE_INITIAL_ASSETS_ID');
                if (!$fileId) {
                    $this->error('GOOGLE_DRIVE_INITIAL_ASSETS_ID not set in .env');
                    return Command::FAILURE;
                }
                // For direct file IDs, masbug/flysystem-google-drive-ext usually needs the ID.
                // Depending on the driver, we might need to use the ID as the path if it's configured that way,
                // or use a specific method. However, the standard Flysystem put/get uses paths.
                // If it's a specific file ID, we might need to handle it differently.
                // Let's assume the user provides a path or we use the ID as a path.
                $this->info("Downloading initial assets (ID/Path: {$fileId})...");
            } else {
                // List files and find the latest backup
                $files = $disk->listContents('.', false);
                $backups = [];
                foreach ($files as $file) {
                    if ($file['type'] === 'file' && str_starts_with($file['path'], 'artwork-backup-')) {
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
                $artworkPath = storage_path('app/public/artwork');
                if (!is_dir($artworkPath)) {
                    mkdir($artworkPath, 0755, true);
                }

                $zip->extractTo($artworkPath);
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
