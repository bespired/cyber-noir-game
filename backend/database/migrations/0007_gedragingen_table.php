<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gedragingen', function (Blueprint $table) {
            $table->id();
            $table->string('naam');
            $table->text('beschrijving')->nullable();
            $table->text('acties')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gedragingen');
    }
};
