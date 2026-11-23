@extends('layouts.app')

@section('title', 'Obrigações')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Obrigações</h1>
            <p class="mt-1 text-sm text-slate-600">Vencimentos ligados aos contatos.</p>
        </div>
        <a href="{{ route('obligations.create') }}" class="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
            Nova obrigação
        </a>
    </div>

    <form method="GET" action="{{ route('obligations.index') }}" class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-end">
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

    <p class="mb-6 text-sm text-slate-500">Os envios ainda não são registrados.</p>

    @if ($obligations->isEmpty() && $search)
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
            <p class="text-slate-700">Nenhuma obrigação encontrada.</p>
        </div>
    @elseif ($obligations->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
            <p class="text-slate-700">Nenhuma obrigação cadastrada.</p>
            <a href="{{ route('obligations.create') }}" class="mt-4 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Cadastrar obrigação
            </a>
        </div>
    @else
        <div class="hidden overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Contato</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Obrigação</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Vencimento</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Mensagem</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Envios no mês</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Pagamento</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($obligations as $obligation)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $obligation->contact->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $obligation->title }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $obligation->due_on->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">Sem envios</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">Sem envios</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                    {{ $obligation->payment_status->label() }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('obligations.show', $obligation) }}" class="font-medium text-slate-700 hover:text-slate-900">Ver</a>
                                    <a href="{{ route('obligations.edit', $obligation) }}" class="font-medium text-slate-700 hover:text-slate-900">Editar</a>
                                    <form method="POST" action="{{ route('obligations.destroy', $obligation) }}" onsubmit="return confirm('Excluir esta obrigação? Esta ação não pode ser desfeita.')">
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
            @foreach ($obligations as $obligation)
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="font-semibold">{{ $obligation->contact->name }}</h2>
                    <p class="mt-1 text-sm text-slate-700">{{ $obligation->title }}</p>
                    <dl class="mt-3 grid gap-2 text-sm text-slate-600">
                        <div class="flex justify-between gap-3"><dt>Vencimento</dt><dd>{{ $obligation->due_on->format('d/m/Y') }}</dd></div>
                        <div class="flex justify-between gap-3"><dt>Mensagem</dt><dd>Sem envios</dd></div>
                        <div class="flex justify-between gap-3"><dt>Envios no mês</dt><dd>Sem envios</dd></div>
                        <div class="flex justify-between gap-3">
                            <dt>Pagamento</dt>
                            <dd>
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                    {{ $obligation->payment_status->label() }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                    <div class="mt-4 flex items-center gap-4 text-sm">
                        <a href="{{ route('obligations.show', $obligation) }}" class="font-medium text-slate-700">Ver</a>
                        <a href="{{ route('obligations.edit', $obligation) }}" class="font-medium text-slate-700">Editar</a>
                        <form method="POST" action="{{ route('obligations.destroy', $obligation) }}" onsubmit="return confirm('Excluir esta obrigação? Esta ação não pode ser desfeita.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-medium text-red-700">Excluir</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($obligations->hasPages())
            <div class="mt-6">
                {{ $obligations->links() }}
            </div>
        @endif
    @endif
@endsection
