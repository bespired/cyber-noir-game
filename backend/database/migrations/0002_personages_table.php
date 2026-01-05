<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('personages', function (Blueprint $table) {
            $table->id();
            $table->string('naam');
            $table->string('rol');
            $table->text('beschrijving');
            $table->text('menselijke_status')->nullable();
            $table->text('replicant_status')->nullable();
            $table->text('motief')->nullable();
            $table->boolean('is_replicant')->default(0);
            $table->boolean('is_playable')->default(0);
            $table->string('type')->default('persoon');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personages');
    }
};
