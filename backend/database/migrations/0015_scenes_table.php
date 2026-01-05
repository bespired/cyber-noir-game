<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('locatie_id')->nullable()->constrained('locaties')->cascadeOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained('sectoren')->nullOnDelete();
            $table->string('titel');
            $table->string('type')->default('walkable-area');
            $table->text('beschrijving');
            $table->string('status')->default('active');
            $table->json('gateways')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenes');
    }
};
