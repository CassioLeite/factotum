@extends('layouts.app')

@section('title', $contact->name)

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">{{ $contact->name }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $contact->company ?: 'Sem empresa' }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('contacts.edit', $contact) }}" class="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Editar
            </a>
            <form method="POST" action="{{ route('contacts.destroy', $contact) }}" onsubmit="return confirm('Excluir este contato? Esta ação não pode ser desfeita.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                    Excluir
                </button>
            </form>
        </div>
    </div>

    <dl class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2 sm:p-6">
        <div>
            <dt class="text-sm text-slate-500">Telefone</dt>
            <dd class="mt-1 font-medium">{{ $contact->phone }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">E-mail</dt>
            <dd class="mt-1 font-medium">{{ $contact->email ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Status</dt>
            <dd class="mt-1 font-medium">{{ $contact->status->label() }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Consentimento</dt>
            <dd class="mt-1 font-medium">{{ $contact->whatsapp_consent ? 'Consentido' : 'Sem consentimento' }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Data do consentimento</dt>
            <dd class="mt-1 font-medium">{{ $contact->whatsapp_consent_at?->format('d/m/Y H:i') ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Atualizado em</dt>
            <dd class="mt-1 font-medium">{{ $contact->updated_at?->format('d/m/Y H:i') }}</dd>
        </div>
    </dl>

    <a href="{{ route('contacts.index') }}" class="mt-6 inline-flex text-sm font-medium text-slate-700 underline decoration-slate-300 underline-offset-4">
        Voltar para a lista
    </a>
@endsection
