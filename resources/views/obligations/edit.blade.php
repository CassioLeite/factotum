@extends('layouts.app')

@section('title', 'Editar obrigação')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">Editar obrigação</h1>
        <p class="mt-1 text-sm text-slate-600">Atualize o vencimento e os dados do cadastro.</p>
    </div>

    <form method="POST" action="{{ route('obligations.update', $obligation) }}" novalidate class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        @csrf
        @method('PUT')
        @include('obligations._form', ['submitLabel' => 'Salvar'])
    </form>
@endsection
