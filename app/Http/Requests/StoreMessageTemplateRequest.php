<?php

namespace App\Http\Requests;

use App\Enums\MessageTemplateStatus;
use App\Services\MessageTemplateService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum as EnumRule;

class StoreMessageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $body = $this->input('body');

        $this->merge([
            'name' => is_string($name) ? trim($name) : $name,
            'body' => is_string($body) ? trim($body) : $body,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('message_templates', 'name')->ignore($this->route('template')),
            ],
            'body' => [
                'required',
                'string',
                'max:1024',
                function (string $attribute, mixed $value, Closure $fail): void {
                    foreach (app(MessageTemplateService::class)->bodyErrors((string) $value) as $message) {
                        $fail($message);
                    }
                },
            ],
            'status' => ['required', Rule::enum(MessageTemplateStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'body' => 'conteúdo',
            'status' => 'status',
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
            'name.unique' => 'Este nome já está cadastrado.',
            'body.required' => 'Informe o conteúdo.',
            'body.max' => 'O conteúdo deve ter no máximo :max caracteres.',
            'status.required' => 'Informe o status.',
            'status.'.EnumRule::class => 'Selecione um status válido.',
        ];
    }
}
