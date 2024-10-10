<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetornoAtendimento extends Model
{
    use HasFactory;


    protected $table = 'retorno_atendimento';
    protected $primaryKey = 'IdRetornoAtendimento';

    protected $fillable = [
        'VerificacaoCombinados',
        'RelatoAtendimento',
        'EstagioMudanca',
        'NovosCombinados',
        'OrientacaoSupervisao',
        'AgendamentoFK',
        'Data'
    ];

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class, 'AgendamentoFK', 'IdAgendamento');
    }
}

