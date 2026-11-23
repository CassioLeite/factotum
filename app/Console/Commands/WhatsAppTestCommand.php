<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class WhatsAppTestCommand extends Command
{
    protected $signature = 'whatsapp:test';

    protected $description = 'Envia o template de teste da Meta para o destinatário autorizado';

    public function handle(WhatsAppService $whatsapp): int
    {
        if (! $this->laravel->environment('local')) {
            $this->error('O teste de conexão só pode ser executado no ambiente local.');

            return self::FAILURE;
        }

        if (! filter_var(config('services.whatsapp.test_mode'), FILTER_VALIDATE_BOOLEAN)) {
            $this->error('O teste de conexão exige o modo de teste ligado.');

            return self::FAILURE;
        }

        if (blank(config('services.whatsapp.token'))
            || blank(config('services.whatsapp.phone_number_id'))
            || blank(config('services.whatsapp.test_recipient'))) {
            $this->error('O token de acesso, o identificador do número ou o destinatário de teste não está configurado.');

            return self::FAILURE;
        }

        $result = $whatsapp->sendTemplate(
            (string) config('services.whatsapp.test_recipient'),
            (string) config('services.whatsapp.default_template'),
            (string) config('services.whatsapp.template_language'),
        );

        if ($result['accepted'] && is_string($result['wamid']) && $result['wamid'] !== '') {
            $this->info($this->redact('Mensagem aceita pela Meta. Identificador: '.$result['wamid']));

            return self::SUCCESS;
        }

        $this->error($this->redact($result['error_message'] ?: 'A Meta não aceitou a mensagem.'));

        return self::FAILURE;
    }

    private function redact(string $text): string
    {
        foreach (['token', 'app_secret', 'webhook_verify_token', 'phone_number_id', 'business_account_id', 'test_recipient'] as $key) {
            $secret = config('services.whatsapp.'.$key);

            if (is_string($secret) && $secret !== '') {
                $text = str_replace($secret, '[oculto]', $text);
            }
        }

        return $text;
    }
}
