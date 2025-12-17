<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('locatie_id')->constrained('locaties')->cascadeOnDelete();
            $table->string('titel');
            $table->string('type')->default('walkable-area')
                ->comment('walkable-area, investigation, interrogation, combat, practice');
            $table->text('beschrijving');
            $table->string('status')->default('active'); // active, completed, locked
            $table->json('gateways')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scenes');
    }
};
