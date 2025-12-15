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
        Schema::table('personages', function (Blueprint $table) {
            $table->boolean('is_playable')->default(false)->after('is_replicant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personages', function (Blueprint $table) {
            $table->dropColumn('is_playable');
        });
    }
};
