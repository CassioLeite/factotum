<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Obligation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ObligationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObligationTest extends TestCase
{
    use RefreshDatabase;

    public function test_obligations_are_listed_by_due_date_in_pages_of_ten(): void
    {
        foreach (range(1, 11) as $day) {
            Obligation::factory()->create([
                'title' => sprintf('Obrigacao %02d', $day),
                'due_on' => sprintf('2026-01-%02d', $day),
            ]);
        }

        $firstPage = $this->get(route('obligations.index'));

        $firstPage->assertOk();
        $firstPage->assertSee('01/01/2026');
        $firstPage->assertSee('10/01/2026');
        $firstPage->assertDontSee('11/01/2026');

        $secondPage = $this->get(route('obligations.index', ['page' => 2]));

        $secondPage->assertOk();
        $secondPage->assertSee('11/01/2026');
        $secondPage->assertDontSee('01/01/2026');
    }

    public function test_list_shows_provisional_message_columns_before_payment(): void
    {
        Obligation::factory()->create([
            'title' => 'IPTU',
            'due_on' => '2026-11-23',
        ]);

        $response = $this->get(route('obligations.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Contato',
            'Obrigação',
            'Vencimento',
            'Mensagem',
            'Envios no mês',
            'Pagamento',
        ]);
        $response->assertSee('Sem envios');
        $response->assertSee('Os envios ainda não são registrados.');
        $response->assertDontSee('Reenviar');
        $response->assertDontSee('Não enviado');
        $response->assertDontSee('Enviada');
    }

    public function test_paid_and_cancelled_obligations_still_show_sem_envios(): void
    {
        Obligation::factory()->paid()->create(['title' => 'IPVA pago']);
        Obligation::factory()->cancelled()->create(['title' => 'Taxa cancelada']);

        $response = $this->get(route('obligations.index'));

        $response->assertOk();
        $response->assertSee('IPVA pago');
        $response->assertSee('Taxa cancelada');
        $response->assertSee('Pago');
        $response->assertSee('Cancelada');
        $response->assertSee('Sem envios');
        $response->assertSeeInOrder(['Mensagem', 'Envios no mês', 'Pagamento']);
        $response->assertDontSee('Reenviar');
    }

    public function test_search_filters_by_title_notes_and_contact_name(): void
    {
        $marina = Contact::factory()->create(['name' => 'Marina Costa']);
        $joao = Contact::factory()->create(['name' => 'Joao Pereira']);

        Obligation::factory()->create([
            'contact_id' => $marina->id,
            'title' => 'IPTU da casa',
            'notes' => 'Guia amarela',
        ]);
        Obligation::factory()->create([
            'contact_id' => $joao->id,
            'title' => 'Licenca',
            'notes' => 'Renovacao',
        ]);

        $this->get(route('obligations.index', ['q' => 'IPTU']))
            ->assertOk()
            ->assertSee('IPTU da casa')
            ->assertDontSee('Licenca');

        $this->get(route('obligations.index', ['q' => 'amarela']))
            ->assertOk()
            ->assertSee('IPTU da casa')
            ->assertDontSee('Licenca');

        $this->get(route('obligations.index', ['q' => 'Marina']))
            ->assertOk()
            ->assertSee('IPTU da casa')
            ->assertDontSee('Joao Pereira');
    }

    public function test_empty_list_and_empty_search_have_distinct_messages(): void
    {
        $this->get(route('obligations.index'))
            ->assertOk()
            ->assertSee('Nenhuma obrigação cadastrada.')
            ->assertSee('Cadastrar obrigação')
            ->assertDontSee('Nenhuma obrigação encontrada.');

        Obligation::factory()->create(['title' => 'IPTU']);

        $this->get(route('obligations.index', ['q' => 'inexistente']))
            ->assertOk()
            ->assertSee('Nenhuma obrigação encontrada.')
            ->assertDontSee('Nenhuma obrigação cadastrada.');
    }

    public function test_search_treats_percent_as_text(): void
    {
        $contact = Contact::factory()->create(['name' => 'Marina Costa']);

        Obligation::factory()->create([
            'contact_id' => $contact->id,
            'title' => 'Licenca anual',
            'notes' => 'Renovacao',
        ]);

        $this->get(route('obligations.index', ['q' => '100%']))
            ->assertOk()
            ->assertDontSee('Licenca anual')
            ->assertDontSee('Marina Costa');
    }

    public function test_store_saves_a_past_due_obligation_with_empty_notes(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->post(route('obligations.store'), [
            'contact_id' => $contact->id,
            'title' => '  IPTU  ',
            'notes' => '',
            'due_on' => '2020-01-15',
            'payment_status' => 'pending',
        ]);

        $response->assertRedirect(route('obligations.index'));
        $response->assertSessionHas('success', 'Obrigação cadastrada.');

        $obligation = Obligation::query()->first();

        $this->assertNotNull($obligation);
        $this->assertSame($contact->id, $obligation->contact_id);
        $this->assertSame('IPTU', $obligation->title);
        $this->assertNull($obligation->notes);
        $this->assertSame('2020-01-15', $obligation->due_on->toDateString());
        $this->assertSame('pending', $obligation->payment_status->value);
    }

    public function test_invalid_store_redirects_back_with_the_typed_values(): void
    {
        $response = $this->from(route('obligations.create'))->post(route('obligations.store'), [
            'contact_id' => 999999,
            'title' => '',
            'due_on' => 'not-a-date',
            'payment_status' => 'overdue',
        ]);

        $response->assertRedirect(route('obligations.create'));
        $response->assertSessionHasErrors(['title', 'contact_id', 'due_on', 'payment_status']);
        $response->assertSessionHasInput('due_on', 'not-a-date');
        $response->assertSessionHasInput('payment_status', 'overdue');
        $this->assertDatabaseCount('obligations', 0);
    }

    public function test_update_changes_the_obligation(): void
    {
        $obligation = Obligation::factory()->create();

        $response = $this->put(route('obligations.update', $obligation), [
            'contact_id' => $obligation->contact_id,
            'title' => 'IPVA',
            'notes' => 'Parcela unica',
            'due_on' => '2026-03-10',
            'payment_status' => 'paid',
        ]);

        $response->assertRedirect(route('obligations.index'));
        $response->assertSessionHas('success', 'Obrigação atualizada.');

        $obligation->refresh();

        $this->assertSame('IPVA', $obligation->title);
        $this->assertSame('Parcela unica', $obligation->notes);
        $this->assertSame('2026-03-10', $obligation->due_on->toDateString());
        $this->assertSame('paid', $obligation->payment_status->value);
    }

    public function test_show_displays_the_obligation_and_unknown_id_is_not_found(): void
    {
        $obligation = Obligation::factory()->create([
            'title' => 'IPTU',
            'notes' => null,
        ]);

        $this->get(route('obligations.show', $obligation))
            ->assertOk()
            ->assertSee('IPTU')
            ->assertSee('Sem envios')
            ->assertSee('—')
            ->assertDontSee('Reenviar')
            ->assertDontSee('Enviar')
            ->assertSee('Excluir esta obrigação? Esta ação não pode ser desfeita.', false);

        $this->get('/obligations/9999')->assertNotFound();
        $this->get('/obligations/9999/edit')->assertNotFound();
        $this->put('/obligations/9999', [])->assertNotFound();
        $this->delete('/obligations/9999')->assertNotFound();
    }

    public function test_destroy_removes_only_the_obligation(): void
    {
        $obligation = Obligation::factory()->create();

        $response = $this->delete(route('obligations.destroy', $obligation));

        $response->assertRedirect(route('obligations.index'));
        $response->assertSessionHas('success', 'Obrigação excluída.');
        $this->assertDatabaseMissing('obligations', ['id' => $obligation->id]);
        $this->assertDatabaseHas('contacts', ['id' => $obligation->contact_id]);
    }

    public function test_contact_with_any_obligation_cannot_be_deleted(): void
    {
        foreach (['paid', 'cancelled'] as $state) {
            $contact = Contact::factory()->create();
            $obligation = Obligation::factory()->{$state}()->create([
                'contact_id' => $contact->id,
            ]);

            $response = $this->from(route('contacts.index'))
                ->delete(route('contacts.destroy', $contact));

            $response->assertRedirect(route('contacts.index'));
            $response->assertSessionHas('error', 'Este contato possui obrigações e não pode ser excluído.');
            $this->assertModelExists($contact);
            $this->assertModelExists($obligation);
        }
    }

    public function test_contact_can_be_deleted_after_the_last_obligation_is_removed(): void
    {
        $contact = Contact::factory()->create();
        $obligation = Obligation::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $this->delete(route('obligations.destroy', $obligation))
            ->assertRedirect(route('obligations.index'));

        $this->delete(route('contacts.destroy', $contact))
            ->assertRedirect(route('contacts.index'))
            ->assertSessionHas('success', 'Contato excluído.');

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
        $this->assertDatabaseMissing('obligations', ['id' => $obligation->id]);
    }

    public function test_seeders_skip_obligations_and_the_test_user_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->seed(ObligationSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('obligations', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_obligation_seeder_creates_eight_records_in_local_and_testing(): void
    {
        foreach (['local', 'testing'] as $environment) {
            Obligation::query()->delete();
            Contact::query()->delete();
            User::query()->delete();
            $this->app['env'] = $environment;

            $this->seed(ObligationSeeder::class);

            $this->assertSame(8, Obligation::query()->count());
        }
    }
}
