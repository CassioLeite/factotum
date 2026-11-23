@extends('layouts.app')

@section('title', $obligation->title)

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">{{ $obligation->title }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $obligation->contact->name }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('obligations.edit', $obligation) }}" class="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Editar
            </a>
            <form method="POST" action="{{ route('obligations.destroy', $obligation) }}" onsubmit="return confirm('Excluir esta obrigação? Esta ação não pode ser desfeita.')">
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
            <dt class="text-sm text-slate-500">Contato</dt>
            <dd class="mt-1 font-medium">{{ $obligation->contact->name }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Título</dt>
            <dd class="mt-1 font-medium">{{ $obligation->title }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-sm text-slate-500">Observação</dt>
            <dd class="mt-1 font-medium whitespace-pre-line">{{ $obligation->notes ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Vencimento</dt>
            <dd class="mt-1 font-medium">{{ $obligation->due_on->format('d/m/Y') }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Mensagem</dt>
            <dd class="mt-1 font-medium">Sem envios</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Envios no mês</dt>
            <dd class="mt-1 font-medium">Sem envios</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Pagamento</dt>
            <dd class="mt-1">
                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                    {{ $obligation->payment_status->label() }}
                </span>
            </dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Atualizado em</dt>
            <dd class="mt-1 font-medium">{{ $obligation->updated_at?->format('d/m/Y H:i') }}</dd>
        </div>
    </dl>

    <a href="{{ route('obligations.index') }}" class="mt-6 inline-flex text-sm font-medium text-slate-700 underline decoration-slate-300 underline-offset-4">
        Voltar para a lista
    </a>
@endsection
