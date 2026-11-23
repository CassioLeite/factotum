<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\MessageTemplate;
use App\Models\Obligation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MessageTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_creates_the_default_template(): void
    {
        $this->assertSame(1, MessageTemplate::query()->count());

        $template = MessageTemplate::query()->first();

        $this->assertNotNull($template);
        $this->assertSame('Lembrete de vencimento', $template->name);
        $this->assertSame('Olá, {{nome}}. Lembramos que {{obrigacao}} vence em {{vencimento}}.', $template->body);
        $this->assertSame('active', $template->status->value);
        $this->assertTrue($template->is_default);
        $this->assertNull($template->meta_template_name);
    }

    public function test_list_shows_columns_and_protects_the_default_template(): void
    {
        $response = $this->get(route('templates.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['Nome', 'Conteúdo', 'Status', 'Ações']);
        $response->assertSee('Padrão');
        $response->assertSee('Estes modelos ainda não são enviados nem sincronizados com a Meta.');
        $response->assertDontSee('Excluir este modelo? Esta ação não pode ser desfeita.', false);
        $response->assertDontSee('Enviar');
    }

    public function test_inactive_template_stays_on_the_list(): void
    {
        MessageTemplate::factory()->inactive()->create([
            'name' => 'Cobranca suspensa',
        ]);

        $this->get(route('templates.index'))
            ->assertOk()
            ->assertSee('Cobranca suspensa')
            ->assertSee('Inativo');
    }

    public function test_templates_are_listed_by_name_in_pages_of_ten(): void
    {
        MessageTemplate::query()->delete();

        $this->get(route('templates.index'))
            ->assertOk()
            ->assertSee('Nenhum modelo cadastrado.');

        foreach (range(1, 11) as $number) {
            MessageTemplate::factory()->create([
                'name' => sprintf('Modelo %02d', $number),
            ]);
        }

        $firstPage = $this->get(route('templates.index'));

        $firstPage->assertOk();
        $firstPage->assertSee('Modelo 01');
        $firstPage->assertSee('Modelo 10');
        $firstPage->assertDontSee('Modelo 11');

        $secondPage = $this->get(route('templates.index', ['page' => 2]));

        $secondPage->assertOk();
        $secondPage->assertSee('Modelo 11');
        $secondPage->assertDontSee('Modelo 01');
    }

    public function test_search_filters_by_name_and_body(): void
    {
        MessageTemplate::factory()->create([
            'name' => 'IPTU municipal',
            'body' => 'Guia amarela',
        ]);
        MessageTemplate::factory()->create([
            'name' => 'Licenca',
            'body' => 'Renovacao',
        ]);

        $this->get(route('templates.index', ['q' => 'IPTU']))
            ->assertOk()
            ->assertSee('IPTU municipal')
            ->assertDontSee('Licenca');

        $this->get(route('templates.index', ['q' => 'amarela']))
            ->assertOk()
            ->assertSee('IPTU municipal')
            ->assertDontSee('Licenca');
    }

    public function test_empty_search_has_its_own_message(): void
    {
        $this->get(route('templates.index', ['q' => 'inexistente']))
            ->assertOk()
            ->assertSee('Nenhum modelo encontrado.')
            ->assertDontSee('Nenhum modelo cadastrado.');
    }

    public function test_search_treats_percent_as_text(): void
    {
        MessageTemplate::factory()->create([
            'name' => 'Licenca anual',
            'body' => 'Renovacao',
        ]);

        $this->get(route('templates.index', ['q' => '100%']))
            ->assertOk()
            ->assertDontSee('Licenca anual');
    }

    public function test_store_saves_a_custom_template_and_ignores_protected_fields(): void
    {
        $response = $this->post(route('templates.store'), [
            'name' => '  Cobranca amigavel  ',
            'body' => 'Olá, {{nome}}. Segue {{obrigacao}} com vencimento em {{vencimento}}.',
            'status' => 'active',
            'is_default' => '1',
            'meta_template_name' => 'aprovado',
        ]);

        $response->assertRedirect(route('templates.index'));
        $response->assertSessionHas('success', 'Modelo cadastrado.');

        $template = MessageTemplate::query()->where('name', 'Cobranca amigavel')->first();

        $this->assertNotNull($template);
        $this->assertFalse($template->is_default);
        $this->assertNull($template->meta_template_name);
        $this->assertSame('Olá, {{nome}}. Segue {{obrigacao}} com vencimento em {{vencimento}}.', $template->body);
    }

    public function test_any_subset_of_the_supported_variables_is_accepted(): void
    {
        $bodies = [
            'Sem variavel' => 'Texto livre sem variavel.',
            'So nome' => 'Olá, {{nome}}.',
            'Nome repetido' => 'Olá, {{nome}}. Olá de novo, {{nome}}.',
            'So vencimento' => 'Vence em {{vencimento}}.',
        ];

        foreach ($bodies as $name => $body) {
            $this->post(route('templates.store'), $this->payload([
                'name' => $name,
                'body' => $body,
            ]))->assertRedirect(route('templates.index'));

            $template = MessageTemplate::query()->where('name', $name)->first();

            $this->assertNotNull($template);
            $this->assertSame($body, $template->body);
            $this->assertFalse($template->is_default);
        }
    }

    public function test_invalid_store_redirects_back_with_the_typed_values(): void
    {
        $response = $this->from(route('templates.create'))->post(route('templates.store'), [
            'name' => '',
            'body' => 'Olá, {{telefone}}',
            'status' => 'archived',
        ]);

        $response->assertRedirect(route('templates.create'));
        $response->assertSessionHasErrors([
            'name',
            'body' => 'A variável {{telefone}} não é suportada. Use {{nome}}, {{obrigacao}} ou {{vencimento}}.',
            'status',
        ]);
        $response->assertSessionHasInput('body', 'Olá, {{telefone}}');
        $this->assertSame(1, MessageTemplate::query()->count());
    }

    public function test_unbalanced_braces_and_spaced_variables_are_rejected(): void
    {
        $spaced = $this->from(route('templates.create'))->post(route('templates.store'), $this->payload([
            'body' => 'Olá, {{ nome }}',
        ]));

        $spaced->assertRedirect(route('templates.create'));
        $spaced->assertSessionHasErrors([
            'body' => 'A variável {{ nome }} não é suportada. Use {{nome}}, {{obrigacao}} ou {{vencimento}}.',
        ]);

        $open = $this->from(route('templates.create'))->post(route('templates.store'), $this->payload([
            'name' => 'Chave aberta',
            'body' => 'Olá, {{nome',
        ]));

        $open->assertRedirect(route('templates.create'));
        $open->assertSessionHasErrors([
            'body' => 'Feche todas as variáveis com {{ e }}.',
        ]);
        $this->assertSame(1, MessageTemplate::query()->count());
    }

    public function test_duplicate_name_is_rejected(): void
    {
        $response = $this->from(route('templates.create'))->post(route('templates.store'), $this->payload([
            'name' => 'Lembrete de vencimento',
        ]));

        $response->assertRedirect(route('templates.create'));
        $response->assertSessionHasErrors([
            'name' => 'Este nome já está cadastrado.',
        ]);
    }

    public function test_body_is_limited_to_1024_characters(): void
    {
        $tooLong = $this->from(route('templates.create'))->post(route('templates.store'), $this->payload([
            'name' => 'Texto longo demais',
            'body' => str_repeat('a', 1025),
        ]));

        $tooLong->assertRedirect(route('templates.create'));
        $tooLong->assertSessionHasErrors('body');
        $this->assertSame(1, MessageTemplate::query()->count());

        $suffix = '{{nome}}';
        $body = str_repeat('a', 1024 - strlen($suffix)).$suffix;

        $this->post(route('templates.store'), $this->payload([
            'name' => 'Texto no limite',
            'body' => $body,
        ]))->assertRedirect(route('templates.index'));

        $template = MessageTemplate::query()->where('name', 'Texto no limite')->first();

        $this->assertNotNull($template);
        $this->assertSame(1024, mb_strlen($template->body));
    }

    public function test_preview_does_not_persist_the_default_content(): void
    {
        $response = $this->from(route('templates.create'))->post(route('templates.store'), [
            'intent' => 'preview',
            'name' => 'Rascunho',
            'body' => 'Olá, {{nome}}. Lembramos que {{obrigacao}} vence em {{vencimento}}.',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('templates.create'));
        $response->assertSessionHas('preview', 'Olá, Ana Lima. Lembramos que IPTU vence em 15/01/2026.');
        $this->assertSame(1, MessageTemplate::query()->count());
    }

    public function test_preview_replaces_only_the_variables_present(): void
    {
        $response = $this->from(route('templates.create'))->post(route('templates.store'), [
            'intent' => 'preview',
            'name' => 'Saudacao',
            'body' => 'Olá, {{nome}}.',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('templates.create'));
        $response->assertSessionHas('preview', 'Olá, Ana Lima.');
        $this->assertStringNotContainsString('IPTU', session('preview'));
        $this->assertStringNotContainsString('15/01/2026', session('preview'));
        $this->assertSame(1, MessageTemplate::query()->count());
    }

    public function test_preview_on_edit_does_not_update_the_template(): void
    {
        $template = MessageTemplate::query()->where('is_default', true)->firstOrFail();
        $updatedAt = $template->updated_at?->toDateTimeString();
        $body = $template->body;

        $response = $this->from(route('templates.edit', $template))->put(route('templates.update', $template), [
            'intent' => 'preview',
            'name' => $template->name,
            'body' => $template->body,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('templates.edit', $template));
        $response->assertSessionHas('preview', 'Olá, Ana Lima. Lembramos que IPTU vence em 15/01/2026.');
        $response->assertSessionMissing('errors');

        $template->refresh();

        $this->assertSame($updatedAt, $template->updated_at?->toDateTimeString());
        $this->assertSame($body, $template->body);
        $this->assertTrue($template->is_default);
    }

    public function test_invalid_preview_does_not_persist(): void
    {
        $response = $this->from(route('templates.create'))->post(route('templates.store'), [
            'intent' => 'preview',
            'name' => 'Rascunho',
            'body' => 'Olá, {{telefone}}',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('templates.create'));
        $response->assertSessionHasErrors('body');
        $response->assertSessionMissing('preview');
        $this->assertSame(1, MessageTemplate::query()->count());
    }

    public function test_show_displays_the_default_template_and_unknown_id_is_not_found(): void
    {
        $template = MessageTemplate::query()->where('is_default', true)->firstOrFail();

        $this->get(route('templates.show', $template))
            ->assertOk()
            ->assertSee($template->body, false)
            ->assertSee('Olá, Ana Lima. Lembramos que IPTU vence em 15/01/2026.')
            ->assertSee('Não vinculado')
            ->assertSee('Este é o modelo padrão e não pode ser excluído.')
            ->assertDontSee('Excluir este modelo? Esta ação não pode ser desfeita.', false)
            ->assertDontSee('Enviar');

        $this->get('/templates/9999')->assertNotFound();
        $this->get('/templates/9999/edit')->assertNotFound();
        $this->put('/templates/9999', [])->assertNotFound();
        $this->delete('/templates/9999')->assertNotFound();
    }

    public function test_default_template_can_be_edited_and_cannot_be_deleted(): void
    {
        $template = MessageTemplate::query()->where('is_default', true)->firstOrFail();

        $response = $this->put(route('templates.update', $template), [
            'name' => $template->name,
            'body' => 'Texto revisado, {{nome}}.',
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('templates.index'));
        $response->assertSessionHas('success', 'Modelo atualizado.');

        $template->refresh();

        $this->assertSame('Texto revisado, {{nome}}.', $template->body);
        $this->assertSame('inactive', $template->status->value);
        $this->assertTrue($template->is_default);

        $this->from(route('templates.index'))
            ->delete(route('templates.destroy', $template))
            ->assertRedirect(route('templates.index'))
            ->assertSessionHas('error', 'O modelo padrão não pode ser excluído.');

        $this->assertModelExists($template);
        $this->assertTrue($template->refresh()->is_default);
    }

    public function test_destroy_removes_only_the_custom_template(): void
    {
        $contact = Contact::factory()->create();
        $obligation = Obligation::factory()->create([
            'contact_id' => $contact->id,
        ]);
        $template = MessageTemplate::factory()->create([
            'name' => 'Temporario',
        ]);

        $this->get(route('templates.show', $template))
            ->assertOk()
            ->assertSee('Excluir este modelo? Esta ação não pode ser desfeita.', false);

        $response = $this->delete(route('templates.destroy', $template));

        $response->assertRedirect(route('templates.index'));
        $response->assertSessionHas('success', 'Modelo excluído.');
        $this->assertDatabaseMissing('message_templates', ['id' => $template->id]);
        $this->assertModelExists($contact);
        $this->assertModelExists($obligation);
    }

    public function test_update_changes_a_custom_template(): void
    {
        $template = MessageTemplate::factory()->create([
            'name' => 'Rascunho',
            'body' => 'Oi',
        ]);

        $this->put(route('templates.update', $template), [
            'name' => 'Rascunho final',
            'body' => 'Oi, {{obrigacao}}.',
            'status' => 'inactive',
        ])->assertRedirect(route('templates.index'))
            ->assertSessionHas('success', 'Modelo atualizado.');

        $template->refresh();

        $this->assertSame('Rascunho final', $template->name);
        $this->assertSame('Oi, {{obrigacao}}.', $template->body);
        $this->assertSame('inactive', $template->status->value);
        $this->assertFalse($template->is_default);
    }

    public function test_seeders_keep_only_the_default_template_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->seed(MessageTemplateSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, MessageTemplate::query()->where('is_default', false)->count());
        $this->assertSame(1, MessageTemplate::query()->where('is_default', true)->count());
        $this->assertDatabaseCount('users', 0);
    }

    public function test_template_seeder_creates_three_custom_templates_in_local_and_testing(): void
    {
        foreach (['local', 'testing'] as $environment) {
            MessageTemplate::query()->where('is_default', false)->delete();
            User::query()->delete();
            $this->app['env'] = $environment;

            $this->seed(MessageTemplateSeeder::class);

            $this->assertSame(3, MessageTemplate::query()->where('is_default', false)->count());
            $this->assertSame(1, MessageTemplate::query()->where('is_default', true)->count());
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Cobranca amigavel',
            'body' => 'Olá, {{nome}}.',
            'status' => 'active',
        ], $overrides);
    }
}
