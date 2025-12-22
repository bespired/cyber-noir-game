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
        Schema::table('scenes', function (Blueprint $table) {
            $table->integer('x')->default(100);
            $table->integer('y')->default(100);
            $table->integer('width')->default(200);
            $table->integer('height')->default(150);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->dropColumn(['x', 'y', 'width', 'height']);
        });
    }
};
