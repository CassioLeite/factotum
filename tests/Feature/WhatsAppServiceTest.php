<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_accepted_template_returns_the_wamid_without_meaning_delivered(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [
                    ['id' => 'wamid.ACEITO'],
                ],
            ]),
        ]);

        $result = $this->service()->sendTemplate('15551230000', 'hello_world', 'en_US');

        $this->assertTrue($result['accepted']);
        $this->assertSame('wamid.ACEITO', $result['wamid']);
        $this->assertNull($result['error_code']);
        $this->assertNull($result['error_message']);
        $this->assertArrayNotHasKey('delivered', $result);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://graph.facebook.com/v25.0/1001/messages'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === '15551230000'
                && $request['type'] === 'template'
                && $request['template']['name'] === 'hello_world'
                && $request['template']['language']['code'] === 'en_US'
                && ! array_key_exists('components', $request['template']);
        });
    }

    public function test_template_parameters_keep_the_given_order(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [
                    ['id' => 'wamid.PARAMS'],
                ],
            ]),
        ]);

        $result = $this->service()->sendTemplate('15551230000', 'lembrete', 'pt_BR', ['Ana', '15/01/2026']);

        $this->assertTrue($result['accepted']);
        $this->assertSame('wamid.PARAMS', $result['wamid']);

        Http::assertSent(function ($request): bool {
            return $request['template']['components'] === [[
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => 'Ana'],
                    ['type' => 'text', 'text' => '15/01/2026'],
                ],
            ]];
        });
    }

    public function test_formatted_authorized_phone_is_sent_as_digits(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [
                    ['id' => 'wamid.DIGITS'],
                ],
            ]),
        ]);

        $result = $this->service()->sendTemplate('+1 (555) 123-0000', 'hello_world', 'en_US');

        $this->assertTrue($result['accepted']);

        Http::assertSent(fn ($request): bool => $request['to'] === '15551230000');
    }

    public function test_missing_token_does_not_call_the_api(): void
    {
        config(['services.whatsapp.token' => null]);
        Http::fake();

        $result = $this->service()->sendTemplate('15551230000', 'hello_world', 'en_US');

        $this->assertFalse($result['accepted']);
        $this->assertNull($result['wamid']);
        $this->assertSame('O token de acesso da Meta não está configurado.', $result['error_message']);
        Http::assertNothingSent();
    }

    public function test_missing_phone_number_id_does_not_call_the_api(): void
    {
        config(['services.whatsapp.phone_number_id' => null]);
        Http::fake();

        $result = $this->service()->sendTemplate('15551230000', 'hello_world', 'en_US');

        $this->assertFalse($result['accepted']);
        $this->assertSame('O identificador do número da Meta não está configurado.', $result['error_message']);
        Http::assertNothingSent();
    }

    public function test_http_401_reports_an_invalid_token_without_leaking_it(): void
    {
        $logged = $this->captureLogs();
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token test-token.',
                    'code' => 190,
                ],
            ], 401),
        ]);

        $result = $this->service()->sendTemplate('15551230000', 'hello_world', 'en_US');

        $this->assertFalse($result['accepted']);
        $this->assertNull($result['wamid']);
        $this->assertSame('190', $result['error_code']);
        $this->assertSame('O token de acesso da Meta é inválido ou expirou.', $result['error_message']);
        $this->assertStringNotContainsString('test-token', json_encode($result));
        $this->assertSame([], $logged->getArrayCopy());
    }

    public function test_http_400_keeps_the_meta_code_and_hides_the_token(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid parameter test-token '.str_repeat('x', 500),
                    'code' => 100,
                ],
            ], 400),
        ]);

        $result = $this->service()->sendTemplate('15551230000', 'hello_world', 'en_US');

        $this->assertFalse($result['accepted']);
        $this->assertSame('100', $result['error_code']);
        $this->assertStringNotContainsString('test-token', $result['error_message']);
        $this->assertLessThanOrEqual(500, mb_strlen($result['error_message']));
        $this->assertStringContainsString('[oculto]', $result['error_message']);
    }

    public function test_error_130497_is_a_recipient_refusal(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Recipient is not allowed.',
                    'code' => 130497,
                ],
            ], 400),
        ]);

        $result = $this->service()->sendTemplate('15551230000', 'hello_world', 'en_US');

        $this->assertFalse($result['accepted']);
        $this->assertNull($result['wamid']);
        $this->assertSame('130497', $result['error_code']);
        $this->assertSame('A Meta recusou este destinatário (130497).', $result['error_message']);
    }

    public function test_http_500_uses_a_safe_description(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response('upstream', 500),
        ]);

        $result = $this->service()->sendTemplate('15551230000', 'hello_world', 'en_US');

        $this->assertFalse($result['accepted']);
        $this->assertNull($result['wamid']);
        $this->assertNull($result['error_code']);
        $this->assertSame('A Meta não aceitou a mensagem.', $result['error_message']);
    }

    public function test_timeout_does_not_escape_as_a_client_exception(): void
    {
        $logged = $this->captureLogs();
        Http::fake(function (): void {
            throw new ConnectionException('cURL error 28: Operation timed out after 15000 milliseconds');
        });

        $result = $this->service()->sendTemplate('15551230000', 'hello_world', 'en_US');

        $this->assertFalse($result['accepted']);
        $this->assertNull($result['wamid']);
        $this->assertNull($result['error_code']);
        $this->assertSame('Não foi possível conectar à Meta.', $result['error_message']);
        $this->assertSame([], $logged->getArrayCopy());
    }

    public function test_connection_failure_uses_the_same_safe_message(): void
    {
        Http::fake(function (): void {
            throw new ConnectionException('cURL error 7: Failed to connect to graph.facebook.com');
        });

        $result = $this->service()->sendTemplate('15551230000', 'hello_world', 'en_US');

        $this->assertFalse($result['accepted']);
        $this->assertSame('Não foi possível conectar à Meta.', $result['error_message']);
        $this->assertStringNotContainsString('graph.facebook.com', $result['error_message']);
    }

    public function test_another_recipient_is_blocked_before_http_in_test_mode(): void
    {
        Http::fake();

        $result = $this->service()->sendTemplate('5511999999999', 'hello_world', 'en_US');

        $this->assertFalse($result['accepted']);
        $this->assertNull($result['wamid']);
        $this->assertSame('No modo de teste, só é possível enviar para o número autorizado.', $result['error_message']);
        Http::assertNothingSent();
    }

    public function test_text_message_uses_the_text_payload_for_the_authorized_recipient(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [
                    ['id' => 'wamid.TEXTO'],
                ],
            ]),
        ]);

        $result = $this->service()->sendText('15551230000', 'Olá');

        $this->assertTrue($result['accepted']);
        $this->assertSame('wamid.TEXTO', $result['wamid']);

        Http::assertSent(function ($request): bool {
            $data = $request->data();

            return $data['type'] === 'text'
                && $data['text'] === ['body' => 'Olá']
                && ! array_key_exists('template', $data);
        });
    }

    public function test_command_sends_hello_world_only_when_the_environment_is_local(): void
    {
        $this->app['env'] = 'local';

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [
                    ['id' => 'wamid.COMANDO'],
                ],
            ]),
        ]);

        $this->artisan('whatsapp:test')
            ->expectsOutput('Mensagem aceita pela Meta. Identificador: wamid.COMANDO')
            ->doesntExpectOutputToContain('test-token')
            ->doesntExpectOutputToContain('test-secret')
            ->doesntExpectOutputToContain('15551230000')
            ->doesntExpectOutputToContain('entregue')
            ->assertSuccessful();

        Http::assertSent(function ($request): bool {
            return $request['template']['name'] === 'hello_world'
                && $request['template']['language']['code'] === 'en_US'
                && $request['to'] === '15551230000'
                && ! array_key_exists('components', $request['template']);
        });

        $this->assertSame(0, Contact::query()->count());
    }

    public function test_command_does_not_call_the_api_outside_the_local_environment(): void
    {
        Http::fake();

        $this->artisan('whatsapp:test')
            ->expectsOutput('O teste de conexão só pode ser executado no ambiente local.')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_command_does_not_call_the_api_when_test_mode_is_off(): void
    {
        $this->app['env'] = 'local';
        config(['services.whatsapp.test_mode' => false]);
        Http::fake();

        $this->artisan('whatsapp:test')
            ->expectsOutput('O teste de conexão exige o modo de teste ligado.')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_command_does_not_call_the_api_when_credentials_are_missing(): void
    {
        $this->app['env'] = 'local';
        config(['services.whatsapp.token' => null]);
        Http::fake();

        $this->artisan('whatsapp:test')
            ->expectsOutput('O token de acesso, o identificador do número ou o destinatário de teste não está configurado.')
            ->doesntExpectOutputToContain('test-token')
            ->assertFailed();

        Http::assertNothingSent();
    }

    private function service(): WhatsAppService
    {
        return $this->app->make(WhatsAppService::class);
    }

    private function captureLogs(): \ArrayObject
    {
        $logged = new \ArrayObject;

        Event::listen(MessageLogged::class, function (MessageLogged $event) use ($logged): void {
            $logged[] = $event->message.' '.json_encode($event->context);
        });

        return $logged;
    }
}
