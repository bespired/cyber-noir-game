<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SystemController extends Controller
{
    public function exportGame()
    {
        try {
            $exitCode = Artisan::call('game:export');
            $output = Artisan::output();

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Game export completed successfully.',
                    'output' => $output
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Game export failed.',
                    'output' => $output
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error executing export: ' . $e->getMessage()
            ], 500);
        }
    }

    public function artworkBackup()
    {
        try {
            // Increase timeout for backup as it involves zip and upload
            set_time_limit(300);
            $exitCode = Artisan::call('app:artwork-backup');
            $output = Artisan::output();

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Artwork backup completed successfully.',
                    'output' => $output
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Artwork backup failed.',
                    'output' => $output
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error executing backup: ' . $e->getMessage()
            ], 500);
        }
    }

    public function artworkInstall()
    {
        try {
            // Increase timeout for install as it involves download and unzip
            set_time_limit(600);
            $exitCode = Artisan::call('app:artwork-install');
            $output = Artisan::output();

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Artwork install completed successfully.',
                    'output' => $output
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Artwork install failed.',
                    'output' => $output
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error executing install: ' . $e->getMessage()
            ], 500);
        }
    }
}
