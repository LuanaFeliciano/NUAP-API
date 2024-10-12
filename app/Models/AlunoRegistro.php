<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlunoRegistro extends Model
{
    use HasFactory;
    
    protected $table = 'aluno_registro';
    protected $primaryKey = 'IdAluno';

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'Nome', 'Curso', 'TipoPagCurso', 'RA', 'Idade', 'Termo',
        'Celular', 'Telefone', 'DataNascimento', 'EstadoCivil',
        'Sexo', 'Residencia', 'FamiliaReside', 'Locomocao', 'TempoEstudoDia'
    ];


    public function formacaoAluno() //LIGADO A FORMACAO ALUNO 1 PRA 1
    {
        return $this->hasOne(FormacaoAluno::class, 'IdAluno', 'IdAluno');
    }

    public function informacoesAjuda() //LIGADO A INFORMACOES AJUDA 1 PRA 1
    {
        return $this->hasOne(InformacoesAjuda::class, 'IdAluno', 'IdAluno');
    }

    public function agendamentos()
    {
        return $this->hasMany(Agendamento::class, 'IdAluno', 'IdAluno');
    }

}
