@extends('layouts.app')

@section('title', 'Novo modelo')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">Novo modelo</h1>
        <p class="mt-1 text-sm text-slate-600">Escreva o texto de um lembrete.</p>
    </div>

    <form method="POST" action="{{ route('templates.store') }}" novalidate class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        @csrf
        @include('templates._form', ['template' => null, 'submitLabel' => 'Cadastrar'])
    </form>
@endsection
