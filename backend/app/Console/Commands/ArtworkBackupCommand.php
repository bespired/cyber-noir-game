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
    protected $description = 'Zip and backup the artwork and glb folders to Google Drive, share it, and update the install ID';

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

            // Find the file ID by listing contents (Flysystem V3 compatible)
            $fileId = null;
            $files = $disk->listContents('', false);
            foreach ($files as $file) {
                // In Flysystem V3, StorageAttributes can contain extra metadata
                // The masbug adapter stores the real Google ID in 'id' or 'path' 
                // depending on configuration, but 'id' in extraMetadata is reliable.
                $extra = $file instanceof \League\Flysystem\FileAttributes ? $file->extraMetadata() : [];
                $realId = $extra['id'] ?? null;
                
                // Check if this is our file by name or if the path matches
                // If human-readable paths are OFF, path() is the ID.
                // If human-readable paths are ON, path() is the name.
                if (basename($file->path()) === $zipFileName || $realId === $zipFileName) {
                    $fileId = $realId ?: $file->path();
                    break;
                }
            }

            // Final fallback: If listContents didn't give us a clear ID, use the Google Service to search
            if (!$fileId || $fileId === $zipFileName) {
                $fileId = $this->searchForFileId($zipFileName);
            }

            if ($fileId) {
                $this->info("File ID: {$fileId}");
                $this->changeFilePermissions($fileId);

                // Update database
                \App\Models\Instelling::updateOrCreate(
                    ['sleutel' => 'asset_install_id'],
                    ['waarde' => $fileId]
                );
                $this->info("Database updated with new asset_install_id.");
            } else {
                $this->warn("Could not determine file ID for permission update.");
            }

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

    /**
     * Change file permissions to public
     */
    protected function changeFilePermissions($fileId)
    {
        $this->info("Updating file permissions for: {$fileId}");

        try {
            $client = new \Google\Client();
            $client->setClientId(config('filesystems.disks.google.clientId'));
            $client->setClientSecret(config('filesystems.disks.google.clientSecret'));
            
            $refreshToken = config('filesystems.disks.google.refreshToken');
            if (!$refreshToken) {
                throw new \Exception('Google Drive Refresh Token is missing from configuration.');
            }
            
            $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);
            if (isset($token['error'])) {
                throw new \Exception('Google Drive Auth Error: ' . ($token['error_description'] ?? $token['error']));
            }
            $client->setAccessToken($token);

            $service = new \Google\Service\Drive($client);

            $newPermission = new \Google\Service\Drive\Permission();
            $newPermission->setType('anyone');
            $newPermission->setRole('reader');

            $service->permissions->create($fileId, $newPermission);

            $this->info('File permissions updated successfully to "Anyone with the link".');
            return true;
        } catch (\Exception $e) {
            $this->error('Failed to update file permissions: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Search for a file ID by name using the Google Drive Service
     */
    protected function searchForFileId($fileName)
    {
        $this->info("Searching Google Drive for File ID of: {$fileName}");

        try {
            $client = new \Google\Client();
            $client->setClientId(config('filesystems.disks.google.clientId'));
            $client->setClientSecret(config('filesystems.disks.google.clientSecret'));
            $client->setAccessToken($client->fetchAccessTokenWithRefreshToken(config('filesystems.disks.google.refreshToken')));

            $service = new \Google\Service\Drive($client);
            $folderId = config('filesystems.disks.google.folderId');

            $query = "name = '{$fileName}' and trashed = false";
            if ($folderId && $folderId !== '/') {
                $query .= " and '{$folderId}' in parents";
            }

            $results = $service->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name)',
                'pageSize' => 1
            ]);

            if (count($results->getFiles()) > 0) {
                $foundId = $results->getFiles()[0]->getId();
                $this->info("Found File ID: {$foundId}");
                return $foundId;
            }

            $this->warn("File not found in Google Drive search.");
            return null;
        } catch (\Exception $e) {
            $this->error("Search failed: " . $e->getMessage());
            return null;
        }
    }
}
