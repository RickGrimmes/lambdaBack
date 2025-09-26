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
        Schema::create('ExcerciseMedia', function (Blueprint $table) {
            $table->id('MED_ID');
            $table->foreignId('MED_EXC_ID')->constrained('Excercises', 'EXC_ID')->onDelete('cascade');
            $table->string('MED_Media1')->nullable();
            $table->string('MED_Media2')->nullable();
            $table->string('MED_Media3')->nullable();
            $table->string('MED_Media4')->nullable();
            $table->string('MED_URL1')->nullable();
            $table->string('MED_URL2')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ExcerciseMedia');
    }
};
