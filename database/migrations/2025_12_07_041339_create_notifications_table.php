<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('NOT_ID');
            $table->unsignedBigInteger('NOT_USR_ID'); // Usuario destinatario
            $table->string('NOT_Title', 255); // Título de la notificación
            $table->text('NOT_Body'); // Cuerpo de la notificación
            $table->unsignedBigInteger('NOT_ROO_ID')->nullable(); 
            $table->enum('NOT_Status', ['unread', 'read'])->default('unread'); 
            $table->timestamps(); 
            
            $table->foreign('NOT_USR_ID')->references('USR_ID')->on('Users')->onDelete('cascade');
            $table->foreign('NOT_ROO_ID')->references('ROO_ID')->on('Rooms')->onDelete('cascade');
            
            // Índices para mejor performance
            $table->index('NOT_USR_ID');
            $table->index('NOT_Status');
            $table->index(['NOT_USR_ID', 'NOT_Status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};