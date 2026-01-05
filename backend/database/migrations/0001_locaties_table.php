<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('locaties', function (Blueprint $table) {
            $table->id();
            $table->string('naam');
            $table->text('beschrijving');
            $table->text('notities')->nullable();
            $table->string('adres')->nullable();
            $table->integer('veiligheidsniveau')->default(0);
            $table->integer('volgorde')->default(0);
            $table->text('spawn_points')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locaties');
    }
};
