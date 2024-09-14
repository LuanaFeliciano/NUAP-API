<?php

use App\Http\Controllers\Agendamento;
use App\Http\Controllers\AlunoRegistro;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Estagiario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//AUTH CONTROLLER
Route::controller(AuthController::class)->group(function(){
    Route::post('register', 'register');
    Route::post('login', 'login');

    Route::get('unauthorized',  'unauthorized')->name('login');//erro de nao autorizado pro sanctum
});

//rotas que necessitam o usuario estar autenticado
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'getUser']);

    //Cadastro alunoe consulta
    Route::post('/cadastrarAluno', [AlunoRegistro::class, 'cadastrarAcademico']);
    Route::get('/consultaAluno', [AlunoRegistro::class, 'getAluno']);

    //cadastro agendamento E CONSULTA
    Route::post('/cadastrarAgendamento', [Agendamento::class, 'cadastrarAgendamento']);
    Route::get('/consultarAgendamento', [Agendamento::class, 'consultarAgendamentosPorEstagiario']);
    
});



//constulrar estagiarios ou atualizar
Route::get('/estagiario', [Estagiario::class, 'ConsultarEstagiarios']);
Route::put('/estagiario', [Estagiario::class, 'AtualizarEstagiario']);


