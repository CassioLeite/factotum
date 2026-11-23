@php
    use App\Enums\ObligationPaymentStatus;

    $selectedContact = old('contact_id', $obligation?->contact_id);
    $selectedPayment = old('payment_status', $obligation?->payment_status?->value ?? ObligationPaymentStatus::Pending->value);
    $dueOn = old('due_on', $obligation?->due_on?->format('Y-m-d'));
@endphp

@if ($errors->any())
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        Revise os campos destacados.
    </div>
@endif

<div class="grid gap-5">
    <div>
        <label for="contact_id" class="mb-1.5 block text-sm font-medium text-slate-700">
            Contato <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">obrigatório</span>
        </label>
        <select
            id="contact_id"
            name="contact_id"
            required
            @class([
                'w-full rounded-lg border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400',
                'border-red-500' => $errors->has('contact_id'),
                'border-slate-300' => ! $errors->has('contact_id'),
            ])
        >
            <option value="">Selecione</option>
            @foreach ($contacts as $contact)
                <option value="{{ $contact->id }}" @selected((string) $selectedContact === (string) $contact->id)>
                    {{ $contact->name }}
                </option>
            @endforeach
        </select>
        @error('contact_id')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="title" class="mb-1.5 block text-sm font-medium text-slate-700">
            Título <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">obrigatório</span>
        </label>
        <input
            id="title"
            name="title"
            type="text"
            value="{{ old('title', $obligation?->title) }}"
            required
            @class([
                'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400',
                'border-red-500' => $errors->has('title'),
                'border-slate-300' => ! $errors->has('title'),
            ])
        >
        @error('title')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="notes" class="mb-1.5 block text-sm font-medium text-slate-700">Observação</label>
        <textarea
            id="notes"
            name="notes"
            rows="4"
            @class([
                'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400',
                'border-red-500' => $errors->has('notes'),
                'border-slate-300' => ! $errors->has('notes'),
            ])
        >{{ old('notes', $obligation?->notes) }}</textarea>
        @error('notes')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="due_on" class="mb-1.5 block text-sm font-medium text-slate-700">
            Vencimento <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">obrigatório</span>
        </label>
        <input
            id="due_on"
            name="due_on"
            type="date"
            value="{{ $dueOn }}"
            required
            @class([
                'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400',
                'border-red-500' => $errors->has('due_on'),
                'border-slate-300' => ! $errors->has('due_on'),
            ])
        >
        @error('due_on')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="payment_status" class="mb-1.5 block text-sm font-medium text-slate-700">
            Pagamento <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">obrigatório</span>
        </label>
        <select
            id="payment_status"
            name="payment_status"
            required
            @class([
                'w-full rounded-lg border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400',
                'border-red-500' => $errors->has('payment_status'),
                'border-slate-300' => ! $errors->has('payment_status'),
            ])
        >
            @foreach (ObligationPaymentStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected($selectedPayment === $status->value)>
                    {{ $status->label() }}
                </option>
            @endforeach
        </select>
        @error('payment_status')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex flex-wrap items-center gap-3">
    <button type="submit" class="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
        {{ $submitLabel }}
    </button>
    <a href="{{ route('obligations.index') }}" class="inline-flex rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
        Cancelar
    </a>
</div>
