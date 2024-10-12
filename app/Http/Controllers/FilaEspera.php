<?php

namespace App\Http\Controllers;

use App\Models\AlunoRegistro;
use Illuminate\Http\Request;

class FilaEspera extends Controller
{
    public function listarAlunosSemAgendamento()
    {
        //alunos que nao tem agendamento estao esperando (fila de espera)
        $alunosSemAgendamento = AlunoRegistro::doesntHave('agendamentos') 
            ->orderBy('created_at', 'asc')
            ->get();

        if ($alunosSemAgendamento->isEmpty()) {
            return response()->json(['message' => 'Nenhum aluno na fila de espera'], 404);
        }

        
        return response()->json($alunosSemAgendamento);
    }
}
