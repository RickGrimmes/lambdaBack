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
        Schema::create('Rooms', function (Blueprint $table) {
            $table->id('ROO_ID');
            $table->string('ROO_Code', 7)->unique();
            $table->string('ROO_Name');
            $table->foreignId('ROO_USR_ID')->constrained('Users', 'USR_ID')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Rooms');
    }
};
