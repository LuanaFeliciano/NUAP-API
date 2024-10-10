<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\PrimeiroAtendimento;
use App\Models\RetornoAtendimento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class Atendimento extends Controller
{

    function RealizarAtendimento(Request $request){
        $agendamento = Agendamento::where('IdAgendamento', $request->Agendamento)->first();
        
        if (!$agendamento) {
            return $this->sendError('Agendamento não encontrado', "Agendamento não encontrado", 404);
        }

        if ($agendamento->PrimeiroAtendimento && $agendamento->PrimeiroAtendimento == true) {
            return $this->primeiroAtendimento($request);
        }else{
            return $this->retornoAtendimento($request);
        }
    }

    function primeiroAtendimento($request){

        $validator = Validator::make($request->all(), [
            'ConstelacaoFamiliar' => 'required|string',
            'ResideComQuem' => 'required|string',
            'RelatoAtendimento' => 'required|string',
            'EstagioMudanca' => 'required|string',
            'Combinados' => 'required|string',
            'Observacao' => 'required|string',
            'OrientacaoSupervisao' => 'required|string', 
            'DataPrimeiroAtendimento' => 'required|date',
            'Agendamento' => 'required|integer', 
        ], [
            'ConstelacaoFamiliar.required' => 'O campo "Constelação Familiar" é obrigatório.',
            'ResideComQuem.required' => 'O campo "Reside Com Quem" é obrigatório.',
            'RelatoAtendimento.required' => 'O campo "Relato de Atendimento" é obrigatório.',
            'EstagioMudanca.required' => 'O campo "Estágio de Mudança" é obrigatório.',
            'Combinados.required' => 'O campo "Combinados" é obrigatório.',
            'Observacao.required' => 'O campo "Observação" é obrigatório.',
            'OrientacaoSupervisao.required' => 'O campo "Orientação de Supervisão" é obrigatório.',
            'DataPrimeiroAtendimento.required' => 'O campo "Data do Primeiro Atendimento" é obrigatório.',
            'DataPrimeiroAtendimento.date' => 'O campo "Data do Primeiro Atendimento" deve ser uma data válida.',
            'Agendamento.required' => 'O "Número do Agendamento" é obrigatório.',
        ]);

        
        if ($validator->fails()) {
            return $this->sendError('Falta de informação', $validator->errors(), 422);
        }

        try {
            
            $atendimento = PrimeiroAtendimento::create([
                'ConstelacaoFamiliar' => $request->ConstelacaoFamiliar,
                'ResideComQuem' => $request->ResideComQuem,
                'RelatoAtendimento' => $request->RelatoAtendimento,
                'EstagioMudanca' => $request->EstagioMudanca,
                'Combinados' => $request->Combinados,
                'Observacao' => $request->Observacao,
                'OrientacaoSupervisao' => $request->OrientacaoSupervisao,
                'DataPrimeiroAtendimento' => $request->DataPrimeiroAtendimento,
                'AgendamentoFK' => $request->Agendamento,
            ]);
            
            $this->atualizarAgendamentoParaOk($request->Agendamento); //quando realiza o atendimento quer dizer que o aluno estava entao a condicao é ok
            $this->reagendamentoAutomatico($request->Agendamento);

            return $this->sendResponse($atendimento, 'Atendimento registrado com sucesso.');
            
        } catch (\Throwable $th) {
            return $this->sendError('Erro ao registrar atendimento', [$th->getMessage()], 500);
        }
    }

    function retornoAtendimento($request){
        $validator = Validator::make($request->all(), [
            'VerificacaoCombinados' => 'required|string',
            'RelatoAtendimento' => 'required|string',
            'EstagioMudanca' => 'required|string',
            'NovosCombinados' => 'required|string',
            'OrientacaoSupervisao' => 'required|string',
            'Data' => 'required|date',
            'Agendamento' => 'required|integer', 
        ], [
            'VerificacaoCombinados.required' => 'O campo "Verificação Combinados" é obrigatório.',
            'RelatoAtendimento.required' => 'O campo "Relato de Atendimento" é obrigatório.',
            'EstagioMudanca.required' => 'O campo "Estágio de Mudança" é obrigatório.',
            'NovosCombinados.required' => 'O campo "Novos Combinados" é obrigatório.',
            'OrientacaoSupervisao.required' => 'O campo "Orientação de Supervisão" é obrigatório.',
            'Data.required' => 'O campo "Data" é obrigatório.',
            'Data.date' => 'O campo "Data" deve ser uma data válida.',
            'Agendamento.required' => 'O "Número do Agendamento" é obrigatório.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Falta de informação', $validator->errors(), 422);
        }

        try {
            $atendimento = RetornoAtendimento::create([
                'VerificacaoCombinados' => $request->VerificacaoCombinados,
                'RelatoAtendimento' => $request->RelatoAtendimento,
                'EstagioMudanca' => $request->EstagioMudanca,
                'NovosCombinados' => $request->NovosCombinados,
                'OrientacaoSupervisao' => $request->OrientacaoSupervisao,
                'Data' => $request->Data,
                'AgendamentoFK' => $request->Agendamento,
            ]);

            $this->atualizarAgendamentoParaOk($request->Agendamento); //quando realiza o atendimento quer dizer que o aluno estava entao a condicao é ok
            $this->reagendamentoAutomatico($request->Agendamento);

            return $this->sendResponse($atendimento, 'Retorno Atendimento registrado com sucesso.');
            
        } catch (\Throwable $th) {
            return $this->sendError('Erro ao registrar o retorno do atendimento', [$th->getMessage()], 500);
        }
    }


    protected function reagendamentoAutomatico($agendamentoId) //REAGENDAMENTO AUTOMATICO
    {
        $agendamentoOriginal = Agendamento::find($agendamentoId);
        
        if (!$agendamentoOriginal) {
            return;
        }

        $dataOriginal = Carbon::parse($agendamentoOriginal->Data);

        //o novo agendamento vai ser na proxima semana
        $novaData = $dataOriginal->addWeek();

        $novoAgendamento = Agendamento::create([
            'user_id' => $agendamentoOriginal->user_id,
            'IdAluno' => $agendamentoOriginal->IdAluno,
            'Condicao' => null,
            'Data' => $novaData,
            'Periodo' => $agendamentoOriginal->Periodo,
            'Sala' => $agendamentoOriginal->Sala,
            'OBS' => 'Reagendamento automático',
            'Cancelado' => false,
        ]);
    }

    protected function atualizarAgendamentoParaOk($agendamentoId)
    {
        $agendamento = Agendamento::find($agendamentoId);
        
        if (!$agendamento) {
            return; 
        }

        $agendamento->Condicao = 'ok';
        $agendamento->save();
    }

}
