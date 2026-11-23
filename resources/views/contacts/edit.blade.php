@extends('layouts.app')

@section('title', 'Editar contato')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">Editar contato</h1>
        <p class="mt-1 text-sm text-slate-600">{{ $contact->name }}</p>
    </div>

    <form method="POST" action="{{ route('contacts.update', $contact) }}" novalidate class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        @csrf
        @method('PUT')
        @include('contacts._form', ['contact' => $contact, 'submitLabel' => 'Salvar'])
    </form>
@endsection
