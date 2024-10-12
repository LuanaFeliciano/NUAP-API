<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\AlunoRegistro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Historico extends Controller
{
    /**
     * Consulta o histórico de atendimento de um aluno ou de um agendamento específico.
     */
    public function consultarHistorico(Request $request)
    {

        $validatedData = $request->validate([
            'IdAluno' => 'required|integer',
            'IdAgendamento' => 'nullable|integer'
        ]);

       
        $aluno = AlunoRegistro::find($validatedData['IdAluno']);

        if (!$aluno) {
            return response()->json(['message' => 'Aluno não encontrado'], 404);
        }

        //se tem IdAgendamento no body buscar apenas esse agendamento
        if (isset($validatedData['IdAgendamento'])) {
            $agendamento = Agendamento::where('IdAgendamento', $validatedData['IdAgendamento'])
                ->where('IdAluno', $validatedData['IdAluno'])
                ->with(['primeiroAtendimento', 'retornosAtendimento'])
                ->first();

            if (!$agendamento) {
                return response()->json(['message' => 'Agendamento não encontrado para este aluno'], 404);
            }

            //montar o histrico
            return response()->json($this->montarHistorico([$agendamento]));
        }

        //se nao tem id agendamento buscar todos o historicos do aluno
        $agendamentos = $aluno->agendamentos()
            ->with(['primeiroAtendimento', 'retornosAtendimento'])
            ->get();

        if ($agendamentos->isEmpty()) {
            return response()->json(['message' => 'Nenhum agendamento encontrado para o aluno'], 404);
        }


        return response()->json($this->montarHistorico($agendamentos));
    }

    /**
     * montar o historico de atendimentos (primeiro e retornos)
     */
    private function montarHistorico($agendamentos)
    {
        $historico = [];

        foreach ($agendamentos as $agendamento) {

            if ($agendamento->primeiroAtendimento) {
                $historico[] = [
                    'tipo' => 'Primeiro Atendimento',
                    'data' => $agendamento->primeiroAtendimento->DataPrimeiroAtendimento,
                    'detalhes' => $agendamento->primeiroAtendimento->toArray()
                ];
            }

            if ($agendamento->retornosAtendimento) {
                foreach ($agendamento->retornosAtendimento as $retorno) {
                    $historico[] = [
                        'tipo' => 'Retorno Atendimento',
                        'data' => $retorno->Data,
                        'detalhes' => $retorno->toArray()
                    ];
                }
            }
        }

        return $historico;
    }
}
