@extends('layouts.app')

@section('title', $template->name)

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold tracking-tight">{{ $template->name }}</h1>
                @if ($template->is_default)
                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">Padrão</span>
                @endif
            </div>
            <p class="mt-1 text-sm text-slate-600">Estes modelos ainda não são enviados nem sincronizados com a Meta.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('templates.edit', $template) }}" class="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Editar
            </a>
            @unless ($template->is_default)
                <form method="POST" action="{{ route('templates.destroy', $template) }}" onsubmit="return confirm('Excluir este modelo? Esta ação não pode ser desfeita.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                        Excluir
                    </button>
                </form>
            @endunless
        </div>
    </div>

    @if ($template->is_default)
        <p class="mb-6 text-sm text-slate-600">Este é o modelo padrão e não pode ser excluído.</p>
    @endif

    <dl class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div>
            <dt class="text-sm text-slate-500">Nome</dt>
            <dd class="mt-1 font-medium">{{ $template->name }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Conteúdo</dt>
            <dd class="mt-1 whitespace-pre-line font-medium">{{ $template->body }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Pré-visualização</dt>
            <dd class="mt-1 whitespace-pre-line font-medium">{{ $preview }}</dd>
            <p class="mt-1 text-sm text-slate-500">Dados demonstrativos: Ana Lima, IPTU e 15/01/2026.</p>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Status</dt>
            <dd class="mt-1">
                <span @class([
                    'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                    'bg-emerald-50 text-emerald-700' => $template->status->value === 'active',
                    'bg-slate-100 text-slate-600' => $template->status->value === 'inactive',
                ])>
                    {{ $template->status->label() }}
                </span>
            </dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Template da Meta</dt>
            <dd class="mt-1 font-medium">Não vinculado</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Atualizado em</dt>
            <dd class="mt-1 font-medium">{{ $template->updated_at?->format('d/m/Y H:i') }}</dd>
        </div>
    </dl>

    <a href="{{ route('templates.index') }}" class="mt-6 inline-flex text-sm font-medium text-slate-700 underline decoration-slate-300 underline-offset-4">
        Voltar para a lista
    </a>
@endsection