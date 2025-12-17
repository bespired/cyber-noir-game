<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('sectoren', function (Blueprint $table) {
            $table->id();
            $table->string('naam')->unique();
            $table->text('beschrijving')->nullable();
            $table->json('ontdek_condities')->nullable()->comment('Moet wanneer op de kaart verschijnen.');
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->integer('width')->default(100);
            $table->integer('height')->default(100);
            $table->string('kaart_coordinaten')->nullable(); // Added to match JSON/Model
            $table->boolean('is_ontdekt')->default(false);   // Added to match JSON/Model
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sectoren');
    }
};
