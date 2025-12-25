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
        Schema::table('locaties', function (Blueprint $table) {
            $table->json('spawn_points')->nullable()->after('veiligheidsniveau');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locaties', function (Blueprint $table) {
            $table->dropColumn('spawn_points');
        });
    }
};
