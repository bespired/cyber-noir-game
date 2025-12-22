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
        Schema::create('locaties', function (Blueprint $table) {
            $table->id();

            // Kerngegevens
            $table->string('naam');
            $table->text('beschrijving')
                ->comment('Sfeer, details, zintuiglijke informatie.');
            $table->text('notities')->nullable();
            $table->string('adres')
                ->nullable()->comment('Cyberpunk adres/coördinaten.');

            // Extra context voor de game
            $table->integer('veiligheidsniveau')
                ->default(0)->comment('Bv. 0: openbaar, 3: zwaar bewaakt.');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locaties');
    }
};
