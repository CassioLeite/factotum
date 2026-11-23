<?php

namespace App\Http\Requests;

use App\Enums\ContactStatus;
use App\Models\Contact;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum as EnumRule;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $company = trim((string) $this->input('company', ''));
        $email = trim((string) $this->input('email', ''));

        $this->merge([
            'name' => is_string($name) ? trim($name) : $name,
            'company' => $company === '' ? null : $company,
            'email' => $email === '' ? null : mb_strtolower($email),
            'whatsapp_consent' => $this->boolean('whatsapp_consent'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'phone' => [
                'bail',
                'required',
                'string',
                'regex:/^[0-9+() \-]+$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

                    if (! preg_match('/^\d{10,15}$/', $digits)) {
                        $fail('Informe o telefone com DDI e DDD, usando de 10 a 15 dígitos.');

                        return;
                    }

                    $current = $this->route('contact');

                    $exists = Contact::query()
                        ->where('phone', $digits)
                        ->when(
                            $current instanceof Contact,
                            fn ($query) => $query->whereKeyNot($current->getKey()),
                        )
                        ->exists();

                    if ($exists) {
                        $fail('Este telefone já está cadastrado.');
                    }
                },
            ],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'status' => ['required', Rule::enum(ContactStatus::class)],
            'whatsapp_consent' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'company' => 'empresa',
            'phone' => 'telefone',
            'email' => 'e-mail',
            'status' => 'status',
            'whatsapp_consent' => 'consentimento WhatsApp',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome.',
            'name.max' => 'O nome deve ter no máximo :max caracteres.',
            'company.max' => 'A empresa deve ter no máximo :max caracteres.',
            'phone.required' => 'Informe o telefone.',
            'phone.regex' => 'Use apenas dígitos, espaços, parênteses, hífens e o sinal de +.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail deve ter no máximo :max caracteres.',
            'status.required' => 'Informe o status.',
            'status.'.EnumRule::class => 'Selecione um status válido.',
            'whatsapp_consent.boolean' => 'O consentimento WhatsApp é inválido.',
        ];
    }
}
