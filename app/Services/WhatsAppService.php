<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    /**
     * @param  array<int, string>  $parameters
     * @return array{accepted: bool, wamid: ?string, error_code: ?string, error_message: ?string}
     */
    public function sendTemplate(string $to, string $name, string $language, array $parameters = []): array
    {
        $blocked = $this->guard($to);

        if ($blocked !== null) {
            return $blocked;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->digits($to),
            'type' => 'template',
            'template' => [
                'name' => $name,
                'language' => [
                    'code' => $language,
                ],
            ],
        ];

        $parameters = array_values($parameters);

        if ($parameters !== []) {
            $payload['template']['components'] = [[
                'type' => 'body',
                'parameters' => array_map(
                    fn (string $value): array => [
                        'type' => 'text',
                        'text' => $value,
                    ],
                    $parameters,
                ),
            ]];
        }

        return $this->post($payload);
    }

    /**
     * @return array{accepted: bool, wamid: ?string, error_code: ?string, error_message: ?string}
     */
    public function sendText(string $to, string $body): array
    {
        $blocked = $this->guard($to);

        if ($blocked !== null) {
            return $blocked;
        }

        return $this->post([
            'messaging_product' => 'whatsapp',
            'to' => $this->digits($to),
            'type' => 'text',
            'text' => [
                'body' => $body,
            ],
        ]);
    }

    public function assertTestRecipient(string $phone): bool
    {
        if (! $this->testMode()) {
            return true;
        }

        $digits = $this->digits($phone);
        $allowed = $this->digits((string) config('services.whatsapp.test_recipient'));

        return $digits !== '' && $allowed !== '' && $digits === $allowed;
    }

    /**
     * @return array{accepted: bool, wamid: ?string, error_code: ?string, error_message: ?string}|null
     */
    private function guard(string $to): ?array
    {
        if (! $this->assertTestRecipient($to)) {
            return $this->failure('No modo de teste, só é possível enviar para o número autorizado.');
        }

        if (blank(config('services.whatsapp.token'))) {
            return $this->failure('O token de acesso da Meta não está configurado.');
        }

        if (blank(config('services.whatsapp.phone_number_id'))) {
            return $this->failure('O identificador do número da Meta não está configurado.');
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{accepted: bool, wamid: ?string, error_code: ?string, error_message: ?string}
     */
    private function post(array $payload): array
    {
        $version = (string) config('services.whatsapp.version');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');

        try {
            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withToken((string) config('services.whatsapp.token'))
                ->acceptJson()
                ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", $payload);
        } catch (ConnectionException) {
            return $this->failure('Não foi possível conectar à Meta.');
        }

        return $this->interpret($response);
    }

    /**
     * @return array{accepted: bool, wamid: ?string, error_code: ?string, error_message: ?string}
     */
    private function interpret(Response $response): array
    {
        $error = $response->json('error');
        $code = is_array($error) && isset($error['code']) ? (string) $error['code'] : null;
        $message = is_array($error) && isset($error['message']) ? (string) $error['message'] : '';

        if ($code === '130497') {
            return $this->failure('A Meta recusou este destinatário (130497).', '130497');
        }

        if ($response->status() === 401 || $code === '190') {
            return $this->failure(
                'O token de acesso da Meta é inválido ou expirou.',
                $code === '190' ? '190' : $code,
            );
        }

        $wamid = $response->json('messages.0.id');

        if ($response->successful() && is_string($wamid) && $wamid !== '') {
            return [
                'accepted' => true,
                'wamid' => $wamid,
                'error_code' => null,
                'error_message' => null,
            ];
        }

        return $this->failure(
            $this->safe($message, 'A Meta não aceitou a mensagem.'),
            $code,
        );
    }

    /**
     * @return array{accepted: bool, wamid: ?string, error_code: ?string, error_message: ?string}
     */
    private function failure(string $message, ?string $code = null): array
    {
        return [
            'accepted' => false,
            'wamid' => null,
            'error_code' => $code,
            'error_message' => $message,
        ];
    }

    private function safe(string $message, string $fallback): string
    {
        $message = trim($message);

        if ($message === '') {
            return $fallback;
        }

        foreach (['token', 'app_secret', 'webhook_verify_token', 'phone_number_id', 'business_account_id', 'test_recipient'] as $key) {
            $secret = config('services.whatsapp.'.$key);

            if (is_string($secret) && $secret !== '') {
                $message = str_replace($secret, '[oculto]', $message);
            }
        }

        return mb_substr($message, 0, 500);
    }

    private function testMode(): bool
    {
        return filter_var(config('services.whatsapp.test_mode'), FILTER_VALIDATE_BOOLEAN);
    }

    private function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
