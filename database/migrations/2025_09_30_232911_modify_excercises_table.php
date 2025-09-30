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
        Schema::table('Excercises', function (Blueprint $table) {
            $table->enum('EXC_DifficultyLevel', ['PRINCIPIANTE', 'INTERMEDIO', 'AVANZADO'])->after('EXC_Instructions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Excercises', function (Blueprint $table) {
            $table->dropColumn('EXC_DifficultyLevel');
        });
    }
};
