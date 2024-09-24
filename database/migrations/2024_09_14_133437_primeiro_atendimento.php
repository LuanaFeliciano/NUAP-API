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
            $table->text('ConstelacaoFamiliar');
            $table->text('ResideComQuem');
            $table->text('RelatoAtendimento');
            $table->string('EstagioMudanca');
            $table->text('Combinados');
            $table->text('Observacao');
            $table->text('OrientacaoSupervisao');
            $table->dateTime('DataPrimeiroAtendimento'); 
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
