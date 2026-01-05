<?php

namespace App\Console\Commands;

use App\Models\Afbeelding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CleanupArtwork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:artwork-cleanup {--dry-run : Only show what would be deleted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove unused artwork files and orphaned database records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN: No files or records will be deleted.');
        }

        $this->cleanupOrphanedRecords($dryRun);
        $this->cleanupUnusedFiles($dryRun);

        $this->info('Cleanup completed.');
    }

    /**
     * Cleanup Afbeelding records where the morphed model no longer exists.
     */
    protected function cleanupOrphanedRecords($dryRun)
    {
        $this->comment('Checking for orphaned database records...');

        $afbeeldingen = Afbeelding::all();
        $orphanedCount = 0;

        foreach ($afbeeldingen as $afbeelding) {
            if (!$afbeelding->imageable) {
                $orphanedCount++;
                $this->warn("Orphaned record found: ID {$afbeelding->id}, Type: {$afbeelding->imageable_type}, Path: {$afbeelding->bestandspad}");

                if (!$dryRun) {
                    // Delete file if it exists
                    if (Storage::disk('public')->exists($afbeelding->bestandspad)) {
                        Storage::disk('public')->delete($afbeelding->bestandspad);
                    }
                    $afbeelding->delete();
                }
            }
        }

        if ($orphanedCount === 0) {
            $this->info('No orphaned database records found.');
        } else {
            $this->info("$orphanedCount orphaned records " . ($dryRun ? 'identified' : 'removed') . ".");
        }
    }

    /**
     * Cleanup files in storage that have no corresponding record in the database.
     */
    protected function cleanupUnusedFiles($dryRun)
    {
        $this->comment('Checking for unused files in storage...');

        $allFiles = Storage::disk('public')->allFiles('artwork');
        $dbPaths = Afbeelding::pluck('bestandspad')->toArray();
        $unusedCount = 0;

        foreach ($allFiles as $file) {
            // Normalize path for comparison (some OS use backslashes)
            $normalizedFile = str_replace('\\', '/', $file);

            // IGNORE: artwork/algemeen/ directory
            if (str_starts_with($normalizedFile, 'artwork/algemeen/')) {
                continue;
            }

            if (!in_array($normalizedFile, $dbPaths)) {
                $unusedCount++;
                $this->warn("Unused file found: $normalizedFile");

                if (!$dryRun) {
                    Storage::disk('public')->delete($file);
                }
            }
        }

        if ($unusedCount === 0) {
            $this->info('No unused files found in storage.');
        } else {
            $this->info("$unusedCount unused files " . ($dryRun ? 'identified' : 'removed') . ".");
        }
    }
}
