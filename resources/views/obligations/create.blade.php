@extends('layouts.app')

@section('title', 'Nova obrigação')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">Nova obrigação</h1>
        <p class="mt-1 text-sm text-slate-600">Cadastre o vencimento ligado a um contato.</p>
    </div>

    <form method="POST" action="{{ route('obligations.store') }}" novalidate class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        @csrf
        @include('obligations._form', ['obligation' => null, 'submitLabel' => 'Cadastrar'])
    </form>
@endsection
