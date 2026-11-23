<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Database\Seeders\ContactSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contacts_are_listed_in_pages_of_ten(): void
    {
        foreach (range(1, 11) as $number) {
            Contact::factory()->create([
                'name' => sprintf('Contato %02d', $number),
            ]);
        }

        $firstPage = $this->get(route('contacts.index'));

        $firstPage->assertOk();
        $firstPage->assertSee('Contato 01');
        $firstPage->assertSee('Ativo');
        $firstPage->assertSee('Sem consentimento');
        $firstPage->assertDontSee('Contato 11');

        $secondPage = $this->get(route('contacts.index', ['page' => 2]));

        $secondPage->assertOk();
        $secondPage->assertSee('Contato 11');
        $secondPage->assertDontSee('Contato 01');
    }

    public function test_pagination_preserves_the_search_term(): void
    {
        foreach (range(1, 11) as $number) {
            Contact::factory()->create([
                'name' => sprintf('Alfa %02d', $number),
                'company' => 'Alfa',
            ]);
        }

        $response = $this->get(route('contacts.index', ['q' => 'Alfa']));

        $response->assertOk();
        $response->assertSee('q=Alfa', false);
        $response->assertDontSee('Alfa 11');
    }

    public function test_search_filters_by_name_company_email_and_phone(): void
    {
        Contact::factory()->create([
            'name' => 'Ana Lima',
            'company' => 'Oficina Sul',
            'email' => 'ana@oficina.test',
            'phone' => '5511988887777',
        ]);

        Contact::factory()->create([
            'name' => 'Bruno Dias',
            'company' => 'Estudio Norte',
            'email' => 'bruno@estudio.test',
            'phone' => '5521977776666',
        ]);

        $this->get(route('contacts.index', ['q' => 'Ana']))
            ->assertOk()
            ->assertSee('Ana Lima')
            ->assertDontSee('Bruno Dias');

        $this->get(route('contacts.index', ['q' => 'Oficina']))
            ->assertOk()
            ->assertSee('Ana Lima')
            ->assertDontSee('Bruno Dias');

        $this->get(route('contacts.index', ['q' => 'estudio.test']))
            ->assertOk()
            ->assertSee('Bruno Dias')
            ->assertDontSee('Ana Lima');

        $this->get(route('contacts.index', ['q' => '2197777']))
            ->assertOk()
            ->assertSee('Bruno Dias')
            ->assertDontSee('Ana Lima');
    }

    public function test_empty_list_and_empty_search_have_distinct_messages(): void
    {
        $this->get(route('contacts.index'))
            ->assertOk()
            ->assertSee('Nenhum contato cadastrado');

        Contact::factory()->create(['name' => 'Ana Lima']);

        $this->get(route('contacts.index', ['q' => 'inexistente']))
            ->assertOk()
            ->assertSee('Nenhum contato encontrado');
    }

    public function test_search_treats_percent_as_text(): void
    {
        Contact::factory()->create([
            'name' => 'Maria Souza',
            'company' => 'Atelier',
            'email' => 'maria@example.com',
            'phone' => '5599888777666',
        ]);

        $this->get(route('contacts.index', ['q' => '100%']))
            ->assertOk()
            ->assertDontSee('Maria Souza');
    }

    public function test_store_normalizes_phone_email_and_consent(): void
    {
        $response = $this->post(route('contacts.store'), $this->payload());

        $response->assertRedirect(route('contacts.index'));
        $response->assertSessionHas('success', 'Contato cadastrado.');

        $this->assertDatabaseHas('contacts', [
            'name' => 'Ana Lima',
            'company' => 'Oficina',
            'phone' => '5511988887777',
            'email' => 'ana@empresa.com',
            'status' => 'active',
            'whatsapp_consent' => true,
        ]);

        $this->assertNotNull(Contact::query()->first()->whatsapp_consent_at);
    }

    public function test_store_without_consent_keeps_timestamp_empty(): void
    {
        $payload = $this->payload();
        unset($payload['whatsapp_consent']);

        $this->post(route('contacts.store'), $payload)
            ->assertRedirect(route('contacts.index'));

        $contact = Contact::query()->first();

        $this->assertFalse($contact->whatsapp_consent);
        $this->assertNull($contact->whatsapp_consent_at);
    }

    public function test_invalid_store_redirects_back_with_the_typed_values(): void
    {
        $response = $this->from(route('contacts.create'))->post(route('contacts.store'), [
            'name' => '',
            'phone' => 'abc',
            'email' => 'nao-e-email',
            'status' => 'archived',
        ]);

        $response->assertRedirect(route('contacts.create'));
        $response->assertSessionHasErrors(['name', 'phone', 'email', 'status']);
        $response->assertSessionHasInput('phone', 'abc');
        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_phone_rejects_letters_and_unsupported_characters(): void
    {
        foreach (['11.988887777', '11988887777a'] as $phone) {
            $response = $this->from(route('contacts.create'))->post(route('contacts.store'), $this->payload([
                'phone' => $phone,
            ]));

            $response->assertRedirect(route('contacts.create'));
            $response->assertSessionHasErrors('phone');
            $response->assertSessionHasInput('phone', $phone);
        }

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_phone_rejects_too_short_and_too_long_numbers(): void
    {
        foreach (['123456789', '1234567890123456'] as $phone) {
            $this->from(route('contacts.create'))
                ->post(route('contacts.store'), $this->payload(['phone' => $phone]))
                ->assertRedirect(route('contacts.create'))
                ->assertSessionHasErrors('phone');
        }

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_duplicate_normalized_phone_is_rejected(): void
    {
        $this->post(route('contacts.store'), $this->payload([
            'phone' => '+55 (11) 98888-7777',
        ]));

        $response = $this->from(route('contacts.create'))->post(route('contacts.store'), $this->payload([
            'name' => 'Outra Pessoa',
            'phone' => '55 11 98888-7777',
        ]));

        $response->assertRedirect(route('contacts.create'));
        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_update_keeps_the_same_phone(): void
    {
        $contact = Contact::factory()->create([
            'phone' => '5511988887777',
        ]);

        $response = $this->put(route('contacts.update', $contact), $this->payload([
            'phone' => '+55 (11) 98888-7777',
        ]));

        $response->assertRedirect(route('contacts.index'));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success', 'Contato atualizado.');
        $this->assertSame('5511988887777', $contact->refresh()->phone);
    }

    public function test_update_cannot_take_another_contacts_phone(): void
    {
        $contact = Contact::factory()->create(['phone' => '5511988887777']);
        Contact::factory()->create(['phone' => '5521977776666']);

        $response = $this->from(route('contacts.edit', $contact))->put(route('contacts.update', $contact), $this->payload([
            'phone' => '+55 (21) 97777-6666',
        ]));

        $response->assertRedirect(route('contacts.edit', $contact));
        $response->assertSessionHasErrors('phone');
        $this->assertSame('5511988887777', $contact->refresh()->phone);
    }

    public function test_show_displays_the_contact_and_unknown_id_is_not_found(): void
    {
        $contact = Contact::factory()->consented()->create([
            'name' => 'Ana Lima',
        ]);

        $this->get(route('contacts.show', $contact))
            ->assertOk()
            ->assertSee('Ana Lima')
            ->assertSee($contact->whatsapp_consent_at->format('d/m/Y H:i'))
            ->assertSee('Excluir este contato? Esta ação não pode ser desfeita.', false);

        $this->get('/contacts/9999')->assertNotFound();
    }

    public function test_destroy_removes_the_contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->delete(route('contacts.destroy', $contact));

        $response->assertRedirect(route('contacts.index'));
        $response->assertSessionHas('success', 'Contato excluído.');
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_seeders_skip_demo_data_outside_local_and_testing(): void
    {
        $this->app['env'] = 'production';

        $this->seed(ContactSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('contacts', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_contact_seeder_creates_demo_contacts_in_local_and_testing(): void
    {
        foreach (['local', 'testing'] as $environment) {
            Contact::query()->delete();
            $this->app['env'] = $environment;

            $this->seed(ContactSeeder::class);

            $this->assertSame(12, Contact::query()->count());
        }
    }

    public function test_database_seeder_creates_the_test_user_only_in_local(): void
    {
        $this->app['env'] = 'local';

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertSame(12, Contact::query()->count());
        $this->assertSame(1, User::query()->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ana Lima',
            'company' => 'Oficina',
            'phone' => '+55 (11) 98888-7777',
            'email' => 'Ana@Empresa.com',
            'status' => 'active',
            'whatsapp_consent' => '1',
        ], $overrides);
    }
}
