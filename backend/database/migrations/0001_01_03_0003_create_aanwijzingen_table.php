<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
/**
 * Run the migrations.
 */
    public function up(): void
    {
        Schema::create('aanwijzingen', function (Blueprint $table) {
            $table->id();

            $table->string('titel');
            $table->text('beschrijving');
            $table->string('type')
                ->comment('Bv. Getuigenis, Vingerafdruk, Gehackt Data File.');

            // Relaties (Foreign Keys)
            // De aanwijzing is GEVONDEN op deze locatie.
            $table->foreignId('locatie_id')
                ->nullable() // Optioneel, sommige aanwijzingen zijn puur deductief.
                ->constrained('locaties')
                ->onDelete('set null');

            // De aanwijzing heeft BETREKKING op dit personage.
            $table->foreignId('personage_id')
                ->nullable() // Optioneel, kan een algemene aanwijzing zijn.
                ->constrained('personages')
                ->onDelete('set null');

            // Game-logica
            $table->boolean('is_kritisch')
                ->default(false)
                ->comment('Is dit een plotelement dat MOET worden gevonden?');

            $table->integer('moeilijkheidsgraad')
                ->default(1)
                ->comment('Hoe moeilijk is het om de aanwijzing te verkrijgen.');

            $table->timestamps();
        });
    }

/**
 * Reverse the migrations.
 */
    public function down(): void
    {
        Schema::dropIfExists('aanwijzingen');
    }
};
