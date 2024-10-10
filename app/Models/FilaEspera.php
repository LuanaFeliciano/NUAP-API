<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FilaEspera extends Model
{
    use HasFactory;
    protected $table = 'fila_espera';
    protected $primaryKey = 'IdFilaEspera';

    protected $fillable = [
        'AindaEspera', 'DataSaidaFila'
    ];

    public function alunoRegistro()//fila_espera ALUNO VAI ESTAR LIGADO NO ALUNO REGISTRO PELO IDALUNO
    {
        return $this->belongsTo(AlunoRegistro::class, 'IdAluno', 'IdAluno');
    }
}
