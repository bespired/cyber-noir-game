<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personages', function (Blueprint $table) {
            $table->id();

            // Kerngegevens
            $table->string('naam');
            $table->string('rol')->comment('Bv. Antiquair, Zangeres, Manager');
            $table->text('beschrijving');

            // De Grijze Zone (Elementen voor de V-K test/Dialoog)
            $table->text('menselijke_status')->nullable()->comment('Echte menselijke kenmerken/zwakheden.');
            $table->text('replicant_status')
                ->nullable()->comment('Vermoedelijke, te perfecte kenmerken.');
            $table->text('motief')
                ->nullable()->comment('Wat willen ze bereiken/verbergen?');

            // De Game Logica (BELANGRIJK: Verborgen voor de speler UI)
            $table->boolean('is_replicant')->default(false)->comment('De waarheid voor de game-engine.');
            $table->boolean('is_playable')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personages');
    }
};
