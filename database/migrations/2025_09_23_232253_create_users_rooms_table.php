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
        Schema::create('Users_Rooms', function (Blueprint $table) {
            $table->id('URO_ID');
            $table->foreignId('URO_ROO_ID')->constrained('Rooms', 'ROO_ID')->onDelete('cascade');
            $table->foreignId('URO_USR_ID')->constrained('Users', 'USR_ID')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Users_Rooms');
    }
};
