<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AfbeeldingUploadController extends Controller
{
    public function upload(Request $request, string $modelType, int $modelId)
    {
        // Allow up to 4MB (4096KB)
        $request->validate(['artwork' => 'required|image|max:4096']);

        // 1. Determine the Model
        $className = ucfirst($modelType);
        $modelClass = 'App\\Models\\' . $className;

        if (!class_exists($modelClass)) {
            return response()->json(['message' => 'Model not found'], 404);
        }

        $item = $modelClass::findOrFail($modelId);

        // 2. Process Image (Resize & Compress)
        $file = $request->file('artwork');
        $filename = $file->hashName(); // Generate unique hash name
        $path = 'artwork/' . $modelType . '/' . $filename;

        // Create manager instance with desired driver
        $manager = new \Intervention\Image\ImageManager(
            new \Intervention\Image\Drivers\Gd\Driver()
        );

        $image = $manager->read($file);

        // Resize if wider than 1920px (maintain aspect ratio)
        if ($image->width() > 1920) {
            $image->scale(width: 1920);
        }

        // Encode as JPG with 75% quality to ensure size < 1MB
        $encoded = $image->toJpeg(75);

        // Store the processed image
        Storage::disk('public')->put($path, (string) $encoded);

        // 3. Create database record
        $item->artwork()->create([
            'bestandspad' => $path,
            'titel' => $request->input('titel', $file->getClientOriginalName()),
        ]);

        return response()->json([
            'message' => 'Artwork succesvol geüpload!',
            'path' => Storage::url($path),
            'artwork' => $item->artwork()->latest()->first()
        ]);
    }
}
