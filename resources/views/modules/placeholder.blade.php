@extends('layouts.app')

@section('title', $title)

@section('content')
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h1 class="text-2xl font-semibold tracking-tight">{{ $title }}</h1>
        <p class="mt-3 text-slate-600">Este módulo será implementado em uma próxima etapa.</p>
        <a href="{{ route('home') }}" class="mt-6 inline-flex rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
            Voltar ao início
        </a>
    </section>
@endsection
