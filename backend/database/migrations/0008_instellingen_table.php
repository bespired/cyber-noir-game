<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('instellingen', function (Blueprint $table) {
            $table->id();
            $table->string('sleutel');
            $table->text('waarde')->nullable();
            $table->timestamps();
            $table->unique(['sleutel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instellingen');
    }
};
