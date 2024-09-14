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
        Schema::create('agendamento', function (Blueprint $table) {
            $table->id('IdAgendamento'); // Chave primária
            $table->foreignId('user_id')->constrained('users'); //id da tabela users conectado ao users
            $table->foreignId('IdAluno')->constrained('aluno_registro', 'IdAluno'); //chave estrangeira para a tabela aluno_registro
            $table->enum('Condicao', ['ok', 'f', 'd'])->nullable(); // f (falta), d (desistencia)
            $table->boolean('PrimeiroAtendimento')->default(false);
            $table->date('Data'); //data do agendamento
            $table->enum('Periodo', ['manha', 'tarde', 'noite']);
            $table->boolean('Cancelado')->default(false);
            $table->string('Sala')->nullable(); //sala do agendamento
            $table->string('OBS')->nullable(); //observacao
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('agendamento');
    }
};
