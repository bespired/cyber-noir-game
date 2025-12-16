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
        Schema::rename('conversations', 'dialogen');
        Schema::rename('notes', 'notities');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('dialogen', 'conversations');
        Schema::rename('notities', 'notes');
    }
};
