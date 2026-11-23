@extends('layouts.app')

@section('title', 'Início')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-semibold tracking-tight">Factotum</h1>
        <p class="mt-2 max-w-2xl text-slate-600">
            Organize os contatos que vão receber mensagens automatizadas no WhatsApp.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold">Início</h2>
            <p class="mt-2 text-sm text-slate-600">Visão geral dos módulos do Factotum.</p>
        </section>

        <section class="rounded-2xl border border-slate-900 bg-slate-900 p-5 text-white shadow-sm">
            <h2 class="text-lg font-semibold">Contatos</h2>
            <p class="mt-2 text-sm text-slate-300">Cadastre, busque e atualize as pessoas que podem receber mensagens.</p>
            <a href="{{ route('contacts.index') }}" class="mt-4 inline-flex rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                Gerenciar contatos
            </a>
        </section>

        @foreach ([
            'obligations' => 'Obrigações',
            'messages' => 'Mensagens',
            'templates' => 'Modelos',
            'settings' => 'Configurações',
        ] as $slug => $label)
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold">{{ $label }}</h2>
                <p class="mt-2 text-sm text-slate-600">Este módulo será implementado em uma próxima etapa.</p>
                <a href="{{ route('modules.show', $slug) }}" class="mt-4 inline-flex text-sm font-medium text-slate-700 underline decoration-slate-300 underline-offset-4 hover:text-slate-900">
                    Abrir {{ $label }}
                </a>
            </section>
        @endforeach
    </div>
@endsection
