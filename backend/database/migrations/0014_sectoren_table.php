<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sectoren', function (Blueprint $table) {
            $table->id();
            $table->string('naam');
            $table->text('beschrijving')->nullable();
            $table->text('ontdek_condities')->nullable();
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->integer('width')->default(100);
            $table->integer('height')->default(100);
            $table->string('kaart_coordinaten')->nullable();
            $table->boolean('is_ontdekt')->default(0);
            $table->timestamps();
            $table->unique(['naam']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sectoren');
    }
};
