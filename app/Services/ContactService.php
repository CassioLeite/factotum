<?php

namespace App\Services;

use App\Models\Contact;
use App\Repositories\ContactRepository;
use App\Repositories\ObligationRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ContactService
{
    public function __construct(
        private ContactRepository $contacts,
        private ObligationRepository $obligations,
    ) {}

    public function paginate(?string $search): LengthAwarePaginator
    {
        return $this->contacts->paginate($search);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Contact
    {
        return $this->contacts->create($this->attributes($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Contact $contact, array $data): Contact
    {
        return $this->contacts->update($contact, $this->attributes($data, $contact));
    }

    public function delete(Contact $contact): bool
    {
        if ($this->obligations->existsFor($contact)) {
            return false;
        }

        $this->contacts->delete($contact);

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, ?Contact $current = null): array
    {
        $consent = (bool) ($data['whatsapp_consent'] ?? false);
        $company = $data['company'] ?? null;
        $email = $data['email'] ?? null;

        if ($consent) {
            $consentedAt = $current?->whatsapp_consent
                ? $current->whatsapp_consent_at
                : now();
        } else {
            $consentedAt = null;
        }

        return [
            'name' => $data['name'],
            'company' => $company === '' ? null : $company,
            'phone' => preg_replace('/\D+/', '', (string) $data['phone']) ?? '',
            'email' => $email === '' ? null : $email,
            'status' => $data['status'],
            'whatsapp_consent' => $consent,
            'whatsapp_consent_at' => $consentedAt,
        ];
    }
}
