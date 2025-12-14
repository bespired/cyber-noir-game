<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sectoren', function (Blueprint $table) {
            $table->id();
            $table->string('naam')->unique();
            $table->text('beschrijving')->nullable();
            $table->string('kaart_coordinaten')->nullable()->comment('X/Y coördinaten voor kaartweergave.');
            $table->boolean('is_ontdekt')->default(false)->comment('Moet op de kaart verschijnen.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sectoren');
    }
};
