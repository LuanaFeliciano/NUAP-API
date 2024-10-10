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
        Schema::create('retorno_atendimento', function (Blueprint $table) {
            $table->id('IdRetornoAtendimento');
            $table->text('RelatoAtendimento');
            $table->text('VerificacaoCombinados');
            $table->string('EstagioMudanca');
            $table->text('NovosCombinados');
            $table->text('OrientacaoSupervisao');
            $table->dateTime('Data'); 
            $table->foreignId('AgendamentoFK')->constrained('agendamento', 'IdAgendamento')->onDelete('cascade'); // FK para a tabela agendamento
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('retorno_atendimento');
    }
};
