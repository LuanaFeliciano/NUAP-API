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
        Schema::create('fila_espera', function (Blueprint $table) {
            $table->id('IdFilaEspera');
            $table->boolean('AindaEspera');
            $table->date('DataSaidaFila');
            $table->foreignId('IdAluno')->constrained('aluno_registro', 'IdAluno')->onDelete('cascade'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('fila_espera');
    }
};
