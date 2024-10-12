<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;



class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'telefone' => 'nullable|string|max:20',
            'celular' => 'required|string|max:20',
            'tipo' => 'required|in:estagiario,coordenadora,atendente',
            'RA' => 'required_if:tipo,estagiario|nullable|string|max:20',
        ], [
            'nome.required' => 'O campo Nome é obrigatório.',
        
            'email.required' => 'O campo Email é obrigatório.',
            'email.email' => 'O Email deve ser um endereço de e-mail válido.',
            'email.unique' => 'O Email informado já está em uso.',
        
            'password.required' => 'O campo Senha é obrigatório.',
            'password.min' => 'A Senha deve ter pelo menos 8 caracteres.',
        
            'celular.required' => 'O campo Celular é obrigatório.',
        
            'tipo.required' => 'O campo Tipo é obrigatório.',
        
            'RA.required_if' => 'O campo RA é obrigatório quando o Tipo é estagiário.',
        ]);
        
        
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 422);       
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('invoice')->plainTextToken;
        $success['nome'] =  $user->nome;
        return $this->sendResponse($success, 'Usuário cadastrado com sucesso');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'O campo Email é obrigatório.',
            'email.email' => 'O Email deve ser um endereço de e-mail válido.',
            'password.required' => 'O campo Senha é obrigatório.',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erro de Validação.', $validator->errors(), 422);
        }

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            $success['token'] = $request->user()->createToken('invoice')->plainTextToken;
            $success['nome'] = $user->nome;
            $success['tipo'] = $user->tipo;
            $success['RA'] = $user->RA;

            return $this->sendResponse($success, 'Usuário logado com sucesso');
        }

        return $this->sendError('Credenciais Inválidas.', [
            'error' => 'As credenciais fornecidas estão incorretas. Verifique seu email e senha e tente novamente.'
        ]);
    }

    public function getUser(Request $request)
    {
        return response()->json($request->user(), 200);
    }

    public function unauthorized()
    {
        return $this->sendError('Unauthorized', ['error' => 'Não autorizado']);
    }
}
