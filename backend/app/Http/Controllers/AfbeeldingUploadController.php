<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AfbeeldingUploadController extends Controller
{
    public function upload(Request $request, string $modelType, int $modelId)
    {
        // Allow images or glb files
        $request->validate([
            'artwork' => 'required|file|max:10240', // Increase max size to 10MB for GLBs
        ]);

        $file = $request->file('artwork');
        $extension = strtolower($file->getClientOriginalExtension());
        $isGlb = $extension === 'glb';

        if (!$isGlb && !in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            return response()->json(['message' => 'Invalid file type. Only images and .glb files are allowed.'], 422);
        }

        // 1. Determine the Model
        $className = ucfirst($modelType);
        $modelClass = 'App\\Models\\' . $className;

        if (!class_exists($modelClass)) {
            return response()->json(['message' => 'Model not found'], 404);
        }

        $item = $modelClass::findOrFail($modelId);

        // 2. Process File
        $filename = $file->hashName();
        $basePath = $isGlb ? 'glb' : 'artwork';
        $path = $basePath . '/' . $modelType . '/' . $filename;

        if ($isGlb) {
            // Store GLB directly
            Storage::disk('public')->putFileAs($basePath . '/' . $modelType, $file, $filename);
        } else {
            // Process Image (Resize & Compress)
            $manager = new \Intervention\Image\ImageManager(
                new \Intervention\Image\Drivers\Gd\Driver()
            );

            $image = $manager->read($file);

            // Resize if wider than 1920px (maintain aspect ratio)
            if ($image->width() > 1920) {
                $image->scale(width: 1920);
            }

            // Encode as JPG with 75% quality
            $encoded = $image->toJpeg(75);

            // Store the processed image
            Storage::disk('public')->put($path, (string) $encoded);
        }

        // 3. Create database record
        $item->artwork()->create([
            'bestandspad' => $path,
            'titel' => $request->input('titel', $file->getClientOriginalName()),
        ]);

        return response()->json([
            'message' => 'File succesvol geüpload!',
            'path' => Storage::url($path),
            'artwork' => $item->artwork()->latest()->first()
        ]);
    }
}
