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

            //o estagiario so pode ter 3 agendamentos no dia
            $agendamentosNoDia = ModelsAgendamento::where('user_id', $user->id)
                ->whereDate('Data', $request->Data)
                ->count();

            if ($agendamentosNoDia >= 3) {
                return $this->sendError('Limite atingido', ['error' => 'O estagiário já possui 3 agendamentos nesse dia.'], 422);
            }

            $temAgendamento = ModelsAgendamento::where('IdAluno', $aluno->IdAluno)->exists();
            $primeiroAtendimento = $temAgendamento ? ($request->PrimeiroAtendimento ?? false) : true;
            //cadastra o agendamento
            $agendamento = ModelsAgendamento::create([
                'user_id' => $user->id,
                'IdAluno' => $aluno->IdAluno,
                'Condicao' => $request->Condicao,
                'PrimeiroAtendimento' => $primeiroAtendimento,
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

            $data = today();
            if (strlen(trim($request->Data)) > 0) {
                $data = $request->Data;
            }

        }else{
            //coordenadora e atendente
            $validator = Validator::make($request->all(), [
                'RaEstagiario' => 'nullable|string|exists:users,RA',
                'Data' => 'nullable|date'
            ], [
                'RaEstagiario.required' => 'O campo RA do estagiário é obrigatório.',
                'RaEstagiario.exists' => 'O RA do estagiário informado não existe.',
                'Data.date' => 'O campo Data deve ser uma data válida.',
            ]);

            if (strlen(trim($request->RaEstagiario)) == 0 && strlen(trim($request->Data)) == 0) {
                //se não tiver ra nem data pesquisa pelos agendaments de hoje
                $data = today();
            } else {
                //se tiver ra usa o ra
                if (strlen(trim($request->RaEstagiario)) > 0) {
                    $raEstagiario = $request->RaEstagiario;
                }
                //se tiver data usa a data infromada
                if (strlen(trim($request->Data)) > 0) {
                    $data = $request->Data;
                }
            } 
        }
        
        if ($validator->fails()) {
            return $this->sendError('Falta de informação', $validator->errors(), 422);
        }
    
        try {
            
            // Consulta o estagiário se o RA for informado
            if (isset($raEstagiario)) {
                $estagiario = User::where('RA', $raEstagiario)->first();
                if (!$estagiario) {
                    return $this->sendError('Estagiário não encontrado', ['RaAluno' => 'O RA do estagiário informado não foi encontrado.'], 404);
                }
            }

            
            $query = ModelsAgendamento::with(['estagiario', 'aluno']);

            //se houver estagiário, filtra por user_id
            if (isset($estagiario)) {
                $query->where('user_id', $estagiario->id);
            }

            
           //se o ra do aluno for no request busca o aluno pelo RA
            if ($request->has('RaAluno')) {
                $aluno = AlunoRegistro::where('RA', $request->RaAluno)->first();
                
                if (!$aluno) {
                    return $this->sendError('Aluno não encontrado', ['RaAluno' => 'O RA do aluno informado não foi encontrado.'], 404);
                }

                $query->where('IdAluno', $aluno->IdAluno);
            }

            // Se houver registro (IdAluno), filtra por IdAluno
            if ($request->has('registro')) {
                $query->where('IdAluno', $request->registro);
            }

            // Se houver data, filtra pela data
            if (isset($data)) {
                $query->whereDate('Data', $data);
            }

            // Obtém os agendamentos
            $agendamentos = $query->orderBy('IdAgendamento')->get();




            $totalAgendamentos = $agendamentos->count();
            $realizados = $agendamentos->where('Condicao', 'ok')->count();
            $desmarcados = $agendamentos->where('Cancelado', true)->count();//desmarcados sao os cancelados
            $faltas = $agendamentos->where('Condicao', 'f')->count();//que teve falta
            $casosNovos = $agendamentos->where('PrimeiroAtendimento', true)->count();
            $casosDesistencia = $agendamentos->where('Condicao', 'd')->count();
            $casosFinalizados = $agendamentos->where('Finalizado', true)->count();
    
            return response()->json([
                'success' => true,
                'agendamentos' => $agendamentos->map(function ($agendamento) {
                    
                    $status = 'Não Atendido';
                    if ($agendamento->Condicao === 'f') {
                        $status = 'Faltou';
                    } elseif ($agendamento->Condicao === 'ok') {
                        $status = 'Atendido';
                    }

                    if ($agendamento->Finalizado == true) {
                        $status = 'Finalizado';
                    }
            
                    return [
                        'Agendamento' => $agendamento->IdAgendamento,

                        'estagiarioId' => $agendamento->user_id,
                        'estagiario' => $agendamento->estagiario->nome,
                        'estagiarioRA' => $agendamento->estagiario->RA,

                        'sala' => $agendamento->Sala,

                        
                        'alunoId' => $agendamento->IdAluno,
                        'aluno' => $agendamento->aluno->Nome,
                        'alunoRA' => $agendamento->aluno->RA,
                        'curso' => $agendamento->aluno->Curso,
                        'termo' => $agendamento->aluno->Termo,
                        
                        'contato' => $agendamento->aluno->Celular,
                        'contatoTelefone' => $agendamento->aluno->Telefone,
                        'condicao' => $agendamento->Condicao,
                        'status' => $status, // Novo campo status
                        'primeiro_atendimento' => $agendamento->PrimeiroAtendimento ? 'Sim' : 'Não',
                        'cancelado' => $agendamento->Cancelado ? 'Sim' : 'Não',
                        'finalizado' => $agendamento->Finalizado ? 'Sim' : 'Não',
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




    public function atualizarCondicao(Request $request, $id)
    {
        $user = Auth::user();

        if (!in_array($user->tipo, ['coordenadora', 'atendente'])) {
            return $this->sendError('Unauthorized', ['error' => 'Você não tem permissão para atualizar a condição do agendamento'], 403);
        }

        
        $validator = Validator::make($request->all(), [
            'Condicao' => 'required|in:ok,f,d', // ok: Atendido, f: Falta, d: Desistência
        ], [
            'Condicao.required' => 'O campo Condição é obrigatório.',
            'Condicao.in' => 'O campo Condição deve ser um dos seguintes valores: ok, f, d.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Falta de informação', $validator->errors(), 422);
        }

        try {
            //Buscar o agendamento
            $agendamento = ModelsAgendamento::find($id);

            if (!$agendamento) {
                return $this->sendError('Agendamento não encontrado', ['Agendamento' => 'Agendamento com esse número não encontrado'], 404);
            }


            //ataualizadndo a condicao
            $agendamento->Condicao = $request->Condicao;
            $agendamento->save();

            return response()->json([
                'success' => true,
                'message' => 'Condição do agendamento atualizada com sucesso!',
                'agendamento' => $agendamento
            ], 200);

        } catch (\Exception $e) {
            return $this->sendError('Erro ao atualizar a condição do agendamento.', $e->getMessage(), 500);
        }
    }



    public function finalizarAgendamentos(Request $request, $idAluno)
    {
        $user = Auth::user();
        if (!in_array($user->tipo, ['coordenadora', 'estagiario'])) {
            return $this->sendError('Unauthorized', ['error' => 'Você não tem permissão para finalizar os agendamentos'], 403);
        }

        try {

            $agendamentos = ModelsAgendamento::where('IdAluno', $idAluno)->get();

            
            if ($agendamentos->isEmpty()) {
                return $this->sendError('Nenhum agendamento encontrado', ['Agendamento' => 'Nenhum agendamento encontrado para esse aluno'], 404);
            }

            //atualiza cada agendamento para finalizado
            foreach ($agendamentos as $agendamento) {
                $agendamento->Finalizado = true;
                $agendamento->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Todos os agendamentos foram finalizados com sucesso!'
            ], 200);

        } catch (\Exception $e) {
            return $this->sendError('Erro ao finalizar os agendamentos.', $e->getMessage(), 500);
        }
    }

    

    
}
