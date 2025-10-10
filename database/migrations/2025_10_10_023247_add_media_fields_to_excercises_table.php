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
            $table->string('EXC_Media1', 250)->nullable()->after('EXC_ROO_ID');
            $table->string('EXC_Media2', 250)->nullable()->after('EXC_Media1');
            $table->string('EXC_Media3', 250)->nullable()->after('EXC_Media2');
            $table->string('EXC_Media4', 250)->nullable()->after('EXC_Media3');
            $table->string('EXC_URL1', 250)->nullable()->after('EXC_Media4');
            $table->string('EXC_URL2', 250)->nullable()->after('EXC_URL1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
            Schema::table('Excercises', function (Blueprint $table) {
                $table->dropColumn([
                    'EXC_Media1',
                    'EXC_Media2',
                    'EXC_Media3',
                    'EXC_Media4',
                    'EXC_URL1',
                    'EXC_URL2',
            ]);
        });
    }
};
