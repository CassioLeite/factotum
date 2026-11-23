<?php

namespace App\Repositories;

use App\Models\Contact;
use App\Models\Obligation;
use Illuminate\Pagination\LengthAwarePaginator;

class ObligationRepository
{
    public const PER_PAGE = 10;

    public function paginate(?string $search): LengthAwarePaginator
    {
        return Obligation::query()
            ->with('contact')
            ->when(filled($search), function ($query) use ($search): void {
                $like = '%'.$this->escapeLike($search).'%';

                $query->where(function ($query) use ($like): void {
                    $query->whereRaw("title like ? escape '\\'", [$like])
                        ->orWhereRaw("notes like ? escape '\\'", [$like])
                        ->orWhereHas('contact', function ($query) use ($like): void {
                            $query->whereRaw("name like ? escape '\\'", [$like]);
                        });
                });
            })
            ->orderBy('due_on')
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    public function existsFor(Contact $contact): bool
    {
        return Obligation::query()
            ->where('contact_id', $contact->id)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Obligation
    {
        return Obligation::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Obligation $obligation, array $attributes): Obligation
    {
        $obligation->update($attributes);

        return $obligation->refresh();
    }

    public function delete(Obligation $obligation): void
    {
        $obligation->delete();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value,
        );
    }
}
