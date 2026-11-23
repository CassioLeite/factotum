<?php

namespace App\Services;

use App\Models\Obligation;
use App\Repositories\ObligationRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ObligationService
{
    public function __construct(private ObligationRepository $obligations) {}

    public function paginate(?string $search): LengthAwarePaginator
    {
        return $this->obligations->paginate($search);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Obligation
    {
        return $this->obligations->create($this->attributes($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Obligation $obligation, array $data): Obligation
    {
        return $this->obligations->update($obligation, $this->attributes($data));
    }

    public function delete(Obligation $obligation): void
    {
        $this->obligations->delete($obligation);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $notes = $data['notes'] ?? null;

        return [
            'contact_id' => $data['contact_id'],
            'title' => $data['title'],
            'notes' => $notes === '' ? null : $notes,
            'due_on' => $data['due_on'],
            'payment_status' => $data['payment_status'],
        ];
    }
}
