<?php

namespace App\Http\Controllers;

use App\Models\Agendamento as ModelsAgendamento;
use App\Models\AlunoRegistro;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Agendamento extends Controller
{
    public function cadastrarAgendamento(Request $request)
    {
        //precisa cadastrar o agendamento colocando o RA do estagiario que vai fazer o atendimento, 
        //colocar o RA do aluno no request e baseado no RA fazer uma busca nos registros dos alunos e pegar o id do aluno registro pra gravar na tabela.

         //verifiano se o usuario tem permissao para cadastrar o academico
         $user = Auth::user(); 

         if (!in_array($user->tipo, ['coordenadora', 'atendente'])) {
             return $this->sendError('Unauthorized', ['error' => 'Você não tem permissão para cadastrar um agendamento'], 403);
         }

         $validator = Validator::make($request->all(), [
            'RaEstagiario' => 'required|string|exists:users,RA',
            'RaAluno' => 'required|string|exists:aluno_registro,RA',
            'Data' => 'required|date',
            'Sala' => 'nullable|string',
            'OBS' => 'nullable|string',
            'Periodo' => 'required|in:manha,tarde,noite',
            'Condicao' => 'nullable|in:ok,f,d', // (ok, falta, desistência)
            'PrimeiroAtendimento' => 'nullable|boolean',
        ], [
            'RaEstagiario.required' => 'O campo RA do estagiário é obrigatório.',
            'RaEstagiario.exists' => 'O RA do estagiário informado não existe.',
    
            'RaAluno.required' => 'O campo RA do aluno é obrigatório.',
            'RaAluno.exists' => 'O RA do aluno informado não existe.',
    
            'Data.required' => 'O campo Data é obrigatório.',
            'Data.date' => 'O campo Data deve ser uma data válida.',
    
            'Periodo.required' => 'O campo Período é obrigatório.',
            'Periodo.in' => 'O campo Período deve ser um dos seguintes valores: manhã, tarde, noite.',
    
            'Condicao.in' => 'O campo Condição deve ser um dos seguintes valores: ok, falta, desistência.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Falta de informação', $validator->errors(), 422);
        }

        try {

            $aluno = AlunoRegistro::where('RA', $request->RaAluno)->firstOrFail();
            $user = User::where('RA', $request->RaEstagiario)->firstOrFail();

            //cadastra o agendamento
            $agendamento = ModelsAgendamento::create([
                'user_id' => $user->id,
                'IdAluno' => $aluno->IdAluno,
                'Condicao' => $request->Condicao,
                'PrimeiroAtendimento' => $request->PrimeiroAtendimento ?? false,
                'Data' => $request->Data,
                'Periodo' => $request->Periodo,
                'Sala' => $request->Sala,
                'OBS' => $request->OBS,
                'Cancelado' => false,
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Agendamento criado com sucesso!',
                'agendamento' => $agendamento
            ], 201);
    
        } catch (\Exception $e) {
            return $this->sendError('Erro ao cadastrar agendamento.', $e->getMessage(), 500);
        }
    }

    public function consultarAgendamentosPorEstagiario(Request $request)
    {
        //TRATATIVA QUE SE O TOKEN NO AUTORIZATION SER DE UM ESTAGIARIO, SO PODE PUXAR OS AGENDAMENTOS DELE 
        $user = Auth::user(); 

        if ($user->tipo == 'estagiario') {
            $raEstagiario = $user->RA;
            $validator = Validator::make($request->all(), [
                'Data' => 'nullable|date'
            ], [
                'Data.date' => 'O campo Data deve ser uma data válida.',
            ]);

        }else{
            //coordenadora e atendente
            $validator = Validator::make($request->all(), [
                'RaEstagiario' => 'required|string|exists:users,RA',
                'Data' => 'nullable|date'
            ], [
                'RaEstagiario.required' => 'O campo RA do estagiário é obrigatório.',
                'RaEstagiario.exists' => 'O RA do estagiário informado não existe.',
                'Data.date' => 'O campo Data deve ser uma data válida.',
            ]);
            $raEstagiario = $request->RaEstagiario;
        }
        
        if ($validator->fails()) {
            return $this->sendError('Falta de informação', $validator->errors(), 422);
        }
    
        try {
            $estagiario = User::where('RA', $raEstagiario)->firstOrFail();
            
            //constula os agendamentos do estagiario
            $query = ModelsAgendamento::with(['estagiario', 'aluno'])
            ->where('user_id', $estagiario->id);
    
            //se ter data filtra pela data
            if ($request->has('Data')) {
                $query->whereDate('Data', $request->Data);
            }
    
            $agendamentos = $query->get();


            $totalAgendamentos = $agendamentos->count();
            $realizados = $agendamentos->where('Condicao', 'ok')->count();
            $desmarcados = $agendamentos->where('Cancelado', true)->count();//desmarcados sao os cancelados
            $faltas = $agendamentos->where('Condicao', 'f')->count();//que teve falta
            $casosNovos = $agendamentos->where('PrimeiroAtendimento', true)->count();
            $casosDesistencia = $agendamentos->where('Condicao', 'd')->count();
            $casosFinalizados = $agendamentos->where('Condicao', 'ok')->count();
    
            return response()->json([
                'success' => true,
                'agendamentos' => $agendamentos->map(function ($agendamento) {
                    return [
                        'estagiario' => $agendamento->estagiario->nome,
                        'sala' => $agendamento->Sala,
                        'aluno' => $agendamento->aluno->Nome,
                        'curso' => $agendamento->aluno->Curso,
                        'termo' => $agendamento->aluno->Termo,
                        'contato' => $agendamento->aluno->Celular,
                        'contatoTelefone' => $agendamento->aluno->Telefone,
                        'condicao' => $agendamento->Condicao,
                        'primeiro_atendimento' => $agendamento->PrimeiroAtendimento ? 'Sim' : 'Não',
                        'cancelado' => $agendamento->Cancelado ? 'Sim' : 'Não',
                        'Data' => $agendamento->Data,
                        'observacoes' => $agendamento->OBS,
                    ];
                }),
                'estatisticas' => [
                    'total_agendamentos' => $totalAgendamentos,
                    'realizados' => $realizados,
                    'desmarcados' => $desmarcados,
                    'faltas' => $faltas,
                    'casos_novos' => $casosNovos,
                    'casos_desistencia' => $casosDesistencia,
                    'casos_finalizados' => $casosFinalizados
                ]
            ], 200);
    
        } catch (\Exception $e) {
            return $this->sendError('Erro ao consultar agendamentos.', $e->getMessage(), 500);
        }
    }
    

    
}
