@extends('layouts.app')

@section('title', 'Editar modelo')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">Editar modelo</h1>
        <p class="mt-1 text-sm text-slate-600">Atualize o texto do lembrete.</p>
    </div>

    <form method="POST" action="{{ route('templates.update', $template) }}" novalidate class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        @csrf
        @method('PUT')
        @include('templates._form', ['submitLabel' => 'Salvar'])
    </form>
@endsection
