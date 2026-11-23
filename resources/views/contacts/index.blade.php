@extends('layouts.app')

@section('title', 'Contatos')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Contatos</h1>
            <p class="mt-1 text-sm text-slate-600">Pessoas cadastradas para as próximas mensagens.</p>
        </div>
        <a href="{{ route('contacts.create') }}" class="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
            Novo contato
        </a>
    </div>

    <form method="GET" action="{{ route('contacts.index') }}" class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-end">
        <div class="flex-1">
            <label for="q" class="mb-1.5 block text-sm font-medium text-slate-700">Buscar</label>
            <input
                id="q"
                name="q"
                type="search"
                value="{{ $search }}"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400"
            >
        </div>
        <button type="submit" class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Buscar
        </button>
    </form>

    @if ($contacts->isEmpty() && $search)
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
            <p class="text-slate-700">Nenhum contato encontrado.</p>
        </div>
    @elseif ($contacts->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
            <p class="text-slate-700">Nenhum contato cadastrado.</p>
            <a href="{{ route('contacts.create') }}" class="mt-4 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Cadastrar contato
            </a>
        </div>
    @else
        <div class="hidden overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Nome</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Empresa</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Telefone</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">E-mail</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Status</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Consentimento</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($contacts as $contact)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $contact->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $contact->company ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $contact->phone }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $contact->email ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                                    'bg-emerald-50 text-emerald-700' => $contact->status->value === 'active',
                                    'bg-slate-100 text-slate-600' => $contact->status->value === 'inactive',
                                ])>
                                    {{ $contact->status->label() }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $contact->whatsapp_consent ? 'Consentido' : 'Sem consentimento' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('contacts.show', $contact) }}" class="font-medium text-slate-700 hover:text-slate-900">Ver</a>
                                    <a href="{{ route('contacts.edit', $contact) }}" class="font-medium text-slate-700 hover:text-slate-900">Editar</a>
                                    <form method="POST" action="{{ route('contacts.destroy', $contact) }}" onsubmit="return confirm('Excluir este contato? Esta ação não pode ser desfeita.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-medium text-red-700 hover:text-red-800">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="grid gap-3 md:hidden">
            @foreach ($contacts as $contact)
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="font-semibold">{{ $contact->name }}</h2>
                        <span @class([
                            'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                            'bg-emerald-50 text-emerald-700' => $contact->status->value === 'active',
                            'bg-slate-100 text-slate-600' => $contact->status->value === 'inactive',
                        ])>
                            {{ $contact->status->label() }}
                        </span>
                    </div>
                    <dl class="mt-3 grid gap-2 text-sm text-slate-600">
                        <div class="flex justify-between gap-3"><dt>Empresa</dt><dd>{{ $contact->company ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt>Telefone</dt><dd>{{ $contact->phone }}</dd></div>
                        <div class="flex justify-between gap-3"><dt>E-mail</dt><dd class="text-right">{{ $contact->email ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt>Consentimento</dt><dd>{{ $contact->whatsapp_consent ? 'Consentido' : 'Sem consentimento' }}</dd></div>
                    </dl>
                    <div class="mt-4 flex items-center gap-4 text-sm">
                        <a href="{{ route('contacts.show', $contact) }}" class="font-medium text-slate-700">Ver</a>
                        <a href="{{ route('contacts.edit', $contact) }}" class="font-medium text-slate-700">Editar</a>
                        <form method="POST" action="{{ route('contacts.destroy', $contact) }}" onsubmit="return confirm('Excluir este contato? Esta ação não pode ser desfeita.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-medium text-red-700">Excluir</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($contacts->hasPages())
            <div class="mt-6">
                {{ $contacts->links() }}
            </div>
        @endif
    @endif
@endsection
