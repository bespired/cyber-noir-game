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
        Schema::create('scene_personages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scene_id')->constrained()->onDelete('cascade');
            $table->foreignId('personage_id')->constrained('personages')->onDelete('cascade');
            $table->string('spawn_point_name')->nullable(); // Which spawn point to use
            $table->json('spawn_condition')->nullable(); // When to spawn (e.g. flag true)
            $table->foreignId('gedrag_id')->nullable()->constrained('gedragingen')->onDelete('set null');
            $table->foreignId('dialoog_id')->nullable()->constrained('dialogen')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scene_personages');
    }
};
