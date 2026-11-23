<?php

namespace App\Repositories;

use App\Models\MessageTemplate;
use Illuminate\Pagination\LengthAwarePaginator;

class MessageTemplateRepository
{
    public const PER_PAGE = 10;

    public function paginate(?string $search): LengthAwarePaginator
    {
        return MessageTemplate::query()
            ->when(filled($search), function ($query) use ($search): void {
                $like = '%'.$this->escapeLike($search).'%';

                $query->where(function ($query) use ($like): void {
                    $query->whereRaw("name like ? escape '\\'", [$like])
                        ->orWhereRaw("body like ? escape '\\'", [$like]);
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
    public function create(array $attributes): MessageTemplate
    {
        return MessageTemplate::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(MessageTemplate $template, array $attributes): MessageTemplate
    {
        $template->update($attributes);

        return $template->refresh();
    }

    public function delete(MessageTemplate $template): void
    {
        $template->delete();
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
