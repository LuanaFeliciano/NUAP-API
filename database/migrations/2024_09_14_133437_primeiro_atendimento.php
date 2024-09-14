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
        Schema::create('primeiro_atendimento', function (Blueprint $table) {
            $table->id('IdPrimeiroAtendimento');
            $table->text('ConstelacaoFamiliar')->nullable();
            $table->text('ResideComQuem')->nullable();
            $table->text('RelatoAtendimento')->nullable();
            $table->string('EstagioMudanca')->nullable();
            $table->text('Combinados')->nullable();
            $table->text('Observacao')->nullable();
            $table->text('OrientacaoSupervisao')->nullable();
            $table->date('DataPrimeiroAtendimento'); 
            $table->foreignId('AgendamentoFK')->constrained('agendamento', 'IdAgendamento')->onDelete('cascade'); // FK para a tabela agendamento
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('primeiro_atendimento');
    }
};
