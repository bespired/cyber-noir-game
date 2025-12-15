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
        Schema::table('sectoren', function (Blueprint $table) {
            $table->integer('x')->default(0)->after('beschrijving');
            $table->integer('y')->default(0)->after('x');
            $table->integer('width')->default(100)->after('y');
            $table->integer('height')->default(100)->after('width');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sectoren', function (Blueprint $table) {
            $table->dropColumn(['x', 'y', 'width', 'height']);
        });
    }
};
