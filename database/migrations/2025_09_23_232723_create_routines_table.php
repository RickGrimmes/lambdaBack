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
        Schema::create('Routines', function (Blueprint $table) {
            $table->id('ROU_ID');
            $table->foreignId('ROU_USR_ID')->constrained('Users', 'USR_ID')->onDelete('cascade');
            $table->foreignId('ROU_EXC_ID')->constrained('Excercises', 'EXC_ID')->onDelete('cascade');
            $table->enum('ROU_Status', ['In Progress', 'Completed']);
            $table->boolean('ROU_Fav');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Routines');
    }
};
