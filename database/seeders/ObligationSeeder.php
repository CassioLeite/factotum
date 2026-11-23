<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Obligation;
use Illuminate\Database\Seeder;

class ObligationSeeder extends Seeder
{
    /**
     * Seed demo obligations for local development and tests.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        if (Contact::query()->doesntExist()) {
            Contact::factory()->count(8)->create();
        }

        $contactIds = Contact::query()->pluck('id');

        foreach (range(1, 8) as $index) {
            Obligation::factory()->create([
                'contact_id' => $contactIds[$index % $contactIds->count()],
            ]);
        }
    }
}
