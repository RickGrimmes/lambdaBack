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
        Schema::table('Users', function (Blueprint $table) {
            $table->string('USR_2FA_Code', 6)->nullable();
            $table->timestamp('USR_2FA_Expires')->nullable();
            $table->boolean('USR_2FA_Enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('Users', function (Blueprint $table) {
            $table->dropColumn(['USR_2FA_Code', 'USR_2FA_Expires', 'USR_2FA_Enabled']);
        });
    }
};
