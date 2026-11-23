<?php

namespace App\Http\Requests;

use App\Enums\ObligationPaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum as EnumRule;

class StoreObligationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $title = $this->input('title');
        $notes = trim((string) $this->input('notes', ''));

        $this->merge([
            'title' => is_string($title) ? trim($title) : $title,
            'notes' => $notes === '' ? null : $notes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contact_id' => ['required', 'integer', Rule::exists('contacts', 'id')],
            'title' => ['required', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'due_on' => ['required', 'date'],
            'payment_status' => ['required', Rule::enum(ObligationPaymentStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'contact_id' => 'contato',
            'title' => 'título',
            'notes' => 'observação',
            'due_on' => 'vencimento',
            'payment_status' => 'pagamento',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contact_id.required' => 'Selecione um contato.',
            'contact_id.exists' => 'Selecione um contato válido.',
            'title.required' => 'Informe o título.',
            'title.max' => 'O título deve ter no máximo :max caracteres.',
            'notes.max' => 'A observação deve ter no máximo :max caracteres.',
            'due_on.required' => 'Informe o vencimento.',
            'due_on.date' => 'Informe um vencimento válido.',
            'payment_status.required' => 'Informe o pagamento.',
            'payment_status.'.EnumRule::class => 'Selecione um pagamento válido.',
        ];
    }
}
