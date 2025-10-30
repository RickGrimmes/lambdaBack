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
        Schema::create('Excercises', function (Blueprint $table) {
            $table->id('EXC_ID');
            $table->string('EXC_Title');
            $table->enum('EXC_Type', ['Calentamiento', 'Calistenia', 'Musculatura', 'Elasticidad', 'Resistencia', 'Médico'])->nullable();
            $table->text('EXC_Instructions')->nullable();
            $table->foreignId('EXC_ROO_ID')->constrained('Rooms', 'ROO_ID')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Excercises');
    }
};
