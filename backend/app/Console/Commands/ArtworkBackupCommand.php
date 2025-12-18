<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ArtworkBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:artwork-backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Zip and backup the artwork folder to Google Drive';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Artwork Backup...');

        $artworkPath = storage_path('app/public/artwork');
        if (!is_dir($artworkPath)) {
            $this->error("Artwork directory not found: {$artworkPath}");
            return Command::FAILURE;
        }

        $zipFileName = 'artwork-backup-' . now()->format('Y-m-d-H-i-s') . '.zip';
        $zipFilePath = storage_path('app/' . $zipFileName);

        $this->info("Creating zip archive: {$zipFileName}");

        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $this->error("Failed to create zip file at: {$zipFilePath}");
            return Command::FAILURE;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($artworkPath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($artworkPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        $this->info("Uploading zip to Google Drive...");

        try {
            $disk = \Illuminate\Support\Facades\Storage::disk('google');
            $fileContent = file_get_contents($zipFilePath);
            $disk->put($zipFileName, $fileContent);

            $this->info("Backup successfully uploaded to Google Drive as {$zipFileName}");
        } catch (\Exception $e) {
            $this->error("Failed to upload to Google Drive: " . $e->getMessage());
            // Cleanup local file even on failure
            if (file_exists($zipFilePath)) {
                unlink($zipFilePath);
            }
            return Command::FAILURE;
        }

        $this->info("Cleaning up local zip file...");
        unlink($zipFilePath);

        $this->info('Backup Complete!');
        return Command::SUCCESS;
    }
}
