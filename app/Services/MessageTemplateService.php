<?php

namespace App\Services;

use App\Models\MessageTemplate;
use App\Repositories\MessageTemplateRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class MessageTemplateService
{
    public const VARIABLES = ['nome', 'obrigacao', 'vencimento'];

    public const SAMPLE = [
        '{{nome}}' => 'Ana Lima',
        '{{obrigacao}}' => 'IPTU',
        '{{vencimento}}' => '15/01/2026',
    ];

    public function __construct(private MessageTemplateRepository $templates) {}

    public function paginate(?string $search): LengthAwarePaginator
    {
        return $this->templates->paginate($search);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MessageTemplate
    {
        return $this->templates->create($this->attributes($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MessageTemplate $template, array $data): MessageTemplate
    {
        return $this->templates->update($template, $this->attributes($data));
    }

    public function delete(MessageTemplate $template): bool
    {
        if ($template->is_default) {
            return false;
        }

        $this->templates->delete($template);

        return true;
    }

    public function preview(string $body): string
    {
        return strtr($body, self::SAMPLE);
    }

    /**
     * @return list<string>
     */
    public function bodyErrors(string $body): array
    {
        preg_match_all('/\{\{([^{}]*)\}\}/', $body, $matches);

        $invalid = [];

        foreach ($matches[1] as $index => $name) {
            if (in_array($name, self::VARIABLES, true)) {
                continue;
            }

            $token = $matches[0][$index];

            if (! in_array($token, $invalid, true)) {
                $invalid[] = $token;
            }
        }

        $errors = [];

        if ($invalid !== []) {
            $errors[] = $this->invalidVariablesMessage($invalid);
        }

        $withoutTokens = preg_replace('/\{\{[^{}]*\}\}/', '', $body) ?? $body;

        if (str_contains($withoutTokens, '{{') || str_contains($withoutTokens, '}}')) {
            $errors[] = 'Feche todas as variáveis com {{ e }}.';
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $body = $data['body'] ?? '';

        return [
            'name' => $data['name'],
            'body' => is_string($body) ? trim($body) : '',
            'status' => $data['status'],
        ];
    }

    /**
     * @param  list<string>  $tokens
     */
    private function invalidVariablesMessage(array $tokens): string
    {
        $allowed = 'Use {{nome}}, {{obrigacao}} ou {{vencimento}}.';

        if (count($tokens) === 1) {
            return 'A variável '.$tokens[0].' não é suportada. '.$allowed;
        }

        $last = array_pop($tokens);

        return 'As variáveis '.implode(', ', $tokens).' e '.$last.' não são suportadas. '.$allowed;
    }
}
