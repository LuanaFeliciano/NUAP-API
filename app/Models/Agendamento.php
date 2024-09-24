<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agendamento extends Model
{
    use HasFactory;

    protected $table = 'agendamento';
    protected $primaryKey = 'IdAgendamento';


    protected $fillable = [
        'user_id', 
        'IdAluno', 
        'Condicao', 
        'PrimeiroAtendimento', 
        'Data', 
        'Finalizado', 
        'Periodo', 
        'Cancelado',
        'Sala',
        'OBS'
    ];

    //relacionamento com a tabela users (estagiario)
    public function estagiario()
    {
        return $this->belongsTo(User::class, 'user_id', 'id'); 
    }

    //relacionamento com a tabela'aluno_registro (aluno)
    public function aluno()
    {
        return $this->belongsTo(AlunoRegistro::class, 'IdAluno', 'IdAluno');
    }

    //relacionamento com a tabela primeiro_atendimento
    public function primeiroAtendimento()
    {
        return $this->hasOne(PrimeiroAtendimento::class, 'AgendamentoFK', 'IdAgendamento');
    }

}
