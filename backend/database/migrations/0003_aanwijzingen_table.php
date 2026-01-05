<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aanwijzingen', function (Blueprint $table) {
            $table->id();
            $table->string('titel');
            $table->text('beschrijving');
            $table->string('type')->nullable();
            $table->foreignId('locatie_id')->nullable()->constrained('locaties')->nullOnDelete();
            $table->foreignId('personage_id')->nullable()->constrained('personages')->nullOnDelete();
            $table->boolean('is_kritisch')->default(0);
            $table->integer('moeilijkheidsgraad')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aanwijzingen');
    }
};
