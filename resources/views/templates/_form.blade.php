@php
    use App\Enums\MessageTemplateStatus;

    $selectedStatus = old('status', $template?->status?->value ?? MessageTemplateStatus::Active->value);
@endphp

@if ($errors->any())
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        Revise os campos destacados.
    </div>
@endif

@if (session('preview'))
    <div class="mb-6 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
        <p class="text-sm font-medium text-slate-700">Pré-visualização</p>
        <p class="mt-2 whitespace-pre-line text-sm text-slate-800">{{ session('preview') }}</p>
        <p class="mt-2 text-sm text-slate-500">Dados demonstrativos: Ana Lima, IPTU e 15/01/2026.</p>
    </div>
@endif

<div class="grid gap-5">
    <div>
        <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">
            Nome <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">obrigatório</span>
        </label>
        <input
            id="name"
            name="name"
            type="text"
            value="{{ old('name', $template?->name) }}"
            required
            @class([
                'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400',
                'border-red-500' => $errors->has('name'),
                'border-slate-300' => ! $errors->has('name'),
            ])
        >
        @error('name')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="body" class="mb-1.5 block text-sm font-medium text-slate-700">
            Conteúdo <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">obrigatório</span>
        </label>
        <textarea
            id="body"
            name="body"
            rows="6"
            required
            @class([
                'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400',
                'border-red-500' => $errors->has('body'),
                'border-slate-300' => ! $errors->has('body'),
            ])
        >{{ old('body', $template?->body) }}</textarea>
        <p class="mt-1.5 text-sm text-slate-500">Variáveis disponíveis: {{ '{{nome}}' }}, {{ '{{obrigacao}}' }} e {{ '{{vencimento}}' }}.</p>
        @error('body')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="mb-1.5 block text-sm font-medium text-slate-700">
            Status <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">obrigatório</span>
        </label>
        <select
            id="status"
            name="status"
            required
            @class([
                'w-full rounded-lg border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400',
                'border-red-500' => $errors->has('status'),
                'border-slate-300' => ! $errors->has('status'),
            ])
        >
            @foreach (MessageTemplateStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>
                    {{ $status->label() }}
                </option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex flex-wrap items-center gap-3">
    <button type="submit" class="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
        {{ $submitLabel }}
    </button>
    <button type="submit" name="intent" value="preview" class="inline-flex rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
        Pré-visualizar
    </button>
    <a href="{{ route('templates.index') }}" class="inline-flex rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
        Cancelar
    </a>
</div>
