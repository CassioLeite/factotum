@extends('layouts.app')

@section('title', 'Modelos')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Modelos</h1>
            <p class="mt-1 text-sm text-slate-600">Textos usados nos lembretes.</p>
        </div>
        <a href="{{ route('templates.create') }}" class="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
            Novo modelo
        </a>
    </div>

    <form method="GET" action="{{ route('templates.index') }}" class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-end">
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

    <p class="mb-6 text-sm text-slate-500">Estes modelos ainda não são enviados nem sincronizados com a Meta.</p>

    @if ($templates->isEmpty() && $search)
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
            <p class="text-slate-700">Nenhum modelo encontrado.</p>
        </div>
    @elseif ($templates->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
            <p class="text-slate-700">Nenhum modelo cadastrado.</p>
            <a href="{{ route('templates.create') }}" class="mt-4 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Cadastrar modelo
            </a>
        </div>
    @else
        <div class="hidden overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Nome</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Conteúdo</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Status</th>
                        <th class="whitespace-nowrap px-4 py-3 font-medium">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($templates as $template)
                        <tr>
                            <td class="px-4 py-3 font-medium">
                                <span class="inline-flex flex-wrap items-center gap-2">
                                    {{ $template->name }}
                                    @if ($template->is_default)
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Padrão</span>
                                    @endif
                                </span>
                            </td>
                            <td class="max-w-xs px-4 py-3 text-slate-600">{{ \Illuminate\Support\Str::limit($template->body, 80) }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                                    'bg-emerald-50 text-emerald-700' => $template->status->value === 'active',
                                    'bg-slate-100 text-slate-600' => $template->status->value === 'inactive',
                                ])>
                                    {{ $template->status->label() }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('templates.show', $template) }}" class="font-medium text-slate-700 hover:text-slate-900">Ver</a>
                                    <a href="{{ route('templates.edit', $template) }}" class="font-medium text-slate-700 hover:text-slate-900">Editar</a>
                                    @unless ($template->is_default)
                                        <form method="POST" action="{{ route('templates.destroy', $template) }}" onsubmit="return confirm('Excluir este modelo? Esta ação não pode ser desfeita.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-red-700 hover:text-red-800">Excluir</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="grid gap-3 md:hidden">
            @foreach ($templates as $template)
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-semibold">{{ $template->name }}</h2>
                        @if ($template->is_default)
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Padrão</span>
                        @endif
                    </div>
                    <p class="mt-2 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($template->body, 80) }}</p>
                    <dl class="mt-3 grid gap-2 text-sm text-slate-600">
                        <div class="flex justify-between gap-3">
                            <dt>Status</dt>
                            <dd>
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                                    'bg-emerald-50 text-emerald-700' => $template->status->value === 'active',
                                    'bg-slate-100 text-slate-600' => $template->status->value === 'inactive',
                                ])>
                                    {{ $template->status->label() }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                    <div class="mt-4 flex items-center gap-4 text-sm">
                        <a href="{{ route('templates.show', $template) }}" class="font-medium text-slate-700">Ver</a>
                        <a href="{{ route('templates.edit', $template) }}" class="font-medium text-slate-700">Editar</a>
                        @unless ($template->is_default)
                            <form method="POST" action="{{ route('templates.destroy', $template) }}" onsubmit="return confirm('Excluir este modelo? Esta ação não pode ser desfeita.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-medium text-red-700">Excluir</button>
                            </form>
                        @endunless
                    </div>
                </article>
            @endforeach
        </div>

        @if ($templates->hasPages())
            <div class="mt-6">
                {{ $templates->links() }}
            </div>
        @endif
    @endif
@endsection
