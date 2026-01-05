<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scene_personages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scene_id')->constrained('scenes')->cascadeOnDelete();
            $table->foreignId('personage_id')->constrained('personages')->cascadeOnDelete();
            $table->string('spawn_point_name')->nullable();
            $table->text('spawn_condition')->nullable();
            $table->foreignId('gedrag_id')->nullable()->constrained('gedragingen')->nullOnDelete();
            $table->foreignId('dialoog_id')->nullable()->constrained('dialogen')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scene_personages');
    }
};
