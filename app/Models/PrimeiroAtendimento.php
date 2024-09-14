<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrimeiroAtendimento extends Model
{
    use HasFactory;

    protected $table = 'primeiro_atendimento';
    protected $primaryKey = 'IdPrimeiroAtendimento';

    protected $fillable = [
        'ConstelacaoFamiliar',
        'ResideComQuem',
        'RelatoAtendimento',
        'EstagioMudanca',
        'Combinados',
        'Observacao',
        'OrientacaoSupervisao',
        'DataPrimeiroAtendimento',
        'AgendamentoFK'
    ];

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class, 'AgendamentoFK', 'IdAgendamento');
    }
}
