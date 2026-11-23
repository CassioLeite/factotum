@extends('layouts.app')

@section('title', 'Novo contato')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">Novo contato</h1>
        <p class="mt-1 text-sm text-slate-600">Preencha os dados de quem pode receber mensagens.</p>
    </div>

    <form method="POST" action="{{ route('contacts.store') }}" novalidate class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        @csrf
        @include('contacts._form', ['contact' => null, 'submitLabel' => 'Cadastrar'])
    </form>
@endsection
