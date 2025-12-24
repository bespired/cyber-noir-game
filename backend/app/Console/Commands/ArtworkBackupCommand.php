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
    protected $description = 'Zip and backup the artwork and glb folders to Google Drive';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Asset Backup...');

        $basePath = storage_path('app/public');
        $directories = ['artwork', 'glb'];

        $zipFileName = 'assets-backup-' . now()->format('Y-m-d-H-i-s') . '.zip';
        $zipFilePath = storage_path('app/' . $zipFileName);

        $this->info("Creating zip archive: {$zipFileName}");

        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $this->error("Failed to create zip file at: {$zipFilePath}");
            return Command::FAILURE;
        }

        foreach ($directories as $dir) {
            $path = $basePath . '/' . $dir;
            if (!is_dir($path)) {
                $this->warn("Directory not found, skipping: {$path}");
                continue;
            }

            $this->info("Adding directory: {$dir}");

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    // Keep the directory structure (artwork/file.jpg or glb/model.glb)
                    $relativePath = $dir . '/' . substr($filePath, strlen($path) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
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
