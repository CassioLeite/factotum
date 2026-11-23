<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Services\ContactService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_new_consent_stores_the_current_timestamp(): void
    {
        $now = Carbon::parse('2026-03-01 12:00:00');
        Carbon::setTestNow($now);

        $contact = $this->service()->create($this->data(consent: true));

        $this->assertTrue($contact->whatsapp_consent);
        $this->assertSame($now->toDateTimeString(), $contact->whatsapp_consent_at?->toDateTimeString());
    }

    public function test_keeping_consent_preserves_the_original_timestamp(): void
    {
        $grantedAt = Carbon::parse('2026-03-01 12:00:00');
        Carbon::setTestNow($grantedAt);

        $contact = $this->service()->create($this->data(consent: true));

        Carbon::setTestNow($grantedAt->copy()->addDay());

        $updated = $this->service()->update($contact, $this->data(consent: true));

        $this->assertSame($grantedAt->toDateTimeString(), $updated->whatsapp_consent_at?->toDateTimeString());
    }

    public function test_removing_consent_clears_the_timestamp(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-01 12:00:00'));

        $contact = $this->service()->create($this->data(consent: true));
        $updated = $this->service()->update($contact, $this->data(consent: false));

        $this->assertFalse($updated->whatsapp_consent);
        $this->assertNull($updated->whatsapp_consent_at);
    }

    public function test_granting_consent_again_stores_a_new_timestamp(): void
    {
        $removedAt = Carbon::parse('2026-03-02 08:00:00');
        $grantedAgainAt = Carbon::parse('2026-03-03 09:30:00');

        Carbon::setTestNow(Carbon::parse('2026-03-01 12:00:00'));
        $contact = $this->service()->create($this->data(consent: true));

        Carbon::setTestNow($removedAt);
        $contact = $this->service()->update($contact, $this->data(consent: false));

        Carbon::setTestNow($grantedAgainAt);
        $updated = $this->service()->update($contact, $this->data(consent: true));

        $this->assertSame($grantedAgainAt->toDateTimeString(), $updated->whatsapp_consent_at?->toDateTimeString());
    }

    private function service(): ContactService
    {
        return app(ContactService::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function data(bool $consent): array
    {
        return [
            'name' => 'Ana Lima',
            'company' => 'Oficina',
            'phone' => '5511988887777',
            'email' => 'ana@empresa.com',
            'status' => 'active',
            'whatsapp_consent' => $consent,
        ];
    }
}
