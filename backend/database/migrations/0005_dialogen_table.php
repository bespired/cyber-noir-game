<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dialogen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personage_id')->constrained('personages')->cascadeOnDelete();
            $table->string('titel');
            $table->text('tree')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialogen');
    }
};
