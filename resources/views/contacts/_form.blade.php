@php
    use App\Enums\ContactStatus;

    $selectedStatus = old('status', $contact?->status?->value ?? ContactStatus::Active->value);
@endphp

@if ($errors->any())
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
        Revise os campos destacados.
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
            value="{{ old('name', $contact?->name) }}"
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
        <label for="company" class="mb-1.5 block text-sm font-medium text-slate-700">Empresa</label>
        <input
            id="company"
            name="company"
            type="text"
            value="{{ old('company', $contact?->company) }}"
            @class([
                'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400',
                'border-red-500' => $errors->has('company'),
                'border-slate-300' => ! $errors->has('company'),
            ])
        >
        @error('company')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="phone" class="mb-1.5 block text-sm font-medium text-slate-700">
            Telefone <span class="text-red-600" aria-hidden="true">*</span><span class="sr-only">obrigatório</span>
        </label>
        <input
            id="phone"
            name="phone"
            type="text"
            inputmode="tel"
            autocomplete="tel"
            value="{{ old('phone', $contact?->phone) }}"
            required
            @class([
                'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400',
                'border-red-500' => $errors->has('phone'),
                'border-slate-300' => ! $errors->has('phone'),
            ])
        >
        <p class="mt-1.5 text-sm text-slate-500">DDI + DDD + número. São aceitos dígitos, espaço, parênteses, hífen e +. Ex.: +55 (11) 98888-7777</p>
        @error('phone')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">E-mail</label>
        <input
            id="email"
            name="email"
            type="email"
            value="{{ old('email', $contact?->email) }}"
            @class([
                'w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400',
                'border-red-500' => $errors->has('email'),
                'border-slate-300' => ! $errors->has('email'),
            ])
        >
        @error('email')
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
            @foreach (ContactStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>
                    {{ $status->label() }}
                </option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="whatsapp_consent" class="flex items-start gap-3 text-sm text-slate-700">
            <input
                id="whatsapp_consent"
                name="whatsapp_consent"
                type="checkbox"
                value="1"
                class="mt-0.5 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                @checked(filter_var(old('whatsapp_consent', $contact?->whatsapp_consent), FILTER_VALIDATE_BOOLEAN))
            >
            <span>
                <span class="font-medium">Consentimento WhatsApp</span>
                <span class="mt-1 block text-slate-500">O contato autorizou receber mensagens pelo WhatsApp.</span>
            </span>
        </label>
        @error('whatsapp_consent')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex flex-wrap items-center gap-3">
    <button type="submit" class="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
        {{ $submitLabel }}
    </button>
    <a href="{{ route('contacts.index') }}" class="inline-flex rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
        Cancelar
    </a>
</div>
