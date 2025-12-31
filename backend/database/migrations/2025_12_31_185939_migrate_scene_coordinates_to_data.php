<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $scenes = \App\Models\Scene::all();
        foreach ($scenes as $scene) {
            $data = $scene->data ?? [];

            // Only copy if keys don't exist to avoid overwriting newer data if re-run
            $data['x'] = $scene->x ?? 0;
            $data['y'] = $scene->y ?? 0;
            $data['width'] = $scene->width ?? 200;
            $data['height'] = $scene->height ?? 150;

            $scene->data = $data;
            $scene->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional: We could remove keys from data, but it's safer to leave them.
    }
};
