<?php

namespace App\Repositories;

use App\Models\Contact;
use Illuminate\Pagination\LengthAwarePaginator;

class ContactRepository
{
    public const PER_PAGE = 10;

    public function paginate(?string $search): LengthAwarePaginator
    {
        return Contact::query()
            ->when(filled($search), function ($query) use ($search): void {
                $like = '%'.$this->escapeLike($search).'%';
                $digits = preg_replace('/\D+/', '', $search) ?? '';

                $query->where(function ($query) use ($like, $digits): void {
                    $query->whereRaw("name like ? escape '\\'", [$like])
                        ->orWhereRaw("company like ? escape '\\'", [$like])
                        ->orWhereRaw("email like ? escape '\\'", [$like]);

                    if ($digits !== '') {
                        $query->orWhereRaw(
                            "phone like ? escape '\\'",
                            ['%'.$this->escapeLike($digits).'%'],
                        );
                    }
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Contact
    {
        return Contact::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Contact $contact, array $attributes): Contact
    {
        $contact->update($attributes);

        return $contact->refresh();
    }

    public function delete(Contact $contact): void
    {
        $contact->delete();
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
