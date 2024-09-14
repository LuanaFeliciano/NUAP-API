<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
class Estagiario extends Controller
{
    public function ConsultarEstagiarios(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'RA' => 'nullable|string|exists:users,RA',
        ], [
            'RA.exists' => 'O RA informado do estagiário não existe.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Falta de informação', $validator->errors(), 422);
        }

        try {
            //pesquisar pelo RA
            if ($request->has('RA')) {
                $estagiario = User::where('RA', $request->RA)
                    ->where('ativo', true)
                    ->where('tipo', 'estagiario')
                    ->firstOrFail();


                $result = [
                        'id' => $estagiario->id,
                        'RA' => $estagiario->RA,
                        'nome' => $estagiario->nome,
                        'email' => $estagiario->email,
                        'telefone' => $estagiario->telefone,
                        'celular' => $estagiario->celular,
                        'ativo' => $estagiario->ativo,
                ];

                return $this->sendResponse($result, 'Estagiário encontrado com sucesso!');
            }

            $estagiarios = User::where('ativo', true)
            ->where('tipo', 'estagiario')
            ->get();

            //buscar todos se nao tive ra no request
            $result = $estagiarios->map(function ($estagiario) {
                return [
                    'id' => $estagiario->id,
                    'RA' => $estagiario->RA,
                    'nome' => $estagiario->nome,
                    'email' => $estagiario->email,
                    'telefone' => $estagiario->telefone,
                    'celular' => $estagiario->celular,
                    'ativo' => $estagiario->ativo,
                ];
            });

            return $this->sendResponse($result, 'Estagiários encontrados com sucesso!');

        } catch (\Exception $e) {
            return $this->sendError('Erro ao consultar estagiários.', $e->getMessage(), 500);
        }
    }



    public function AtualizarEstagiario(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'RA' => 'required|string|exists:users,RA',
            'email' => 'nullable|email',
            'telefone' => 'nullable|string',
            'celular' => 'nullable|string',
            'ativo' => 'nullable|boolean',
        ], [
            'RA.required' => 'O campo RA é obrigatório.',
            'RA.exists' => 'O RA informado não existe.',
            'email.email' => 'O campo email deve ser um endereço de e-mail válido.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Falta de informação', $validator->errors(), 422);
        }

        try {

            $estagiario = User::where('RA', $request->RA)
                ->where('tipo', 'estagiario')
                ->firstOrFail();


            if ($request->has('email')) {
                $estagiario->email = $request->email;
            }
            if ($request->has('telefone')) {
                $estagiario->telefone = $request->telefone;
            }
            if ($request->has('celular')) {
                $estagiario->celular = $request->celular;
            }
            if ($request->has('ativo')) {
                $estagiario->ativo = $request->ativo;
            }

            $estagiario->save();

            return $this->sendResponse([
                'id' => $estagiario->id,
                'RA' => $estagiario->RA,
                'nome' => $estagiario->nome,
                'email' => $estagiario->email,
                'telefone' => $estagiario->telefone,
                'celular' => $estagiario->celular,
                'ativo' => $estagiario->ativo,
            ], 'Estagiário atualizado com sucesso!');
        } catch (\Exception $e) {
            return $this->sendError('Erro ao atualizar estagiário.', $e->getMessage(), 500);
        }
    }



}
