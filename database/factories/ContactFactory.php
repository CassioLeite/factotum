<?php

namespace Database\Factories;

use App\Enums\ContactStatus;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'company' => fake()->optional()->company(),
            'phone' => '55'.fake()->unique()->numerify('119########'),
            'email' => fake()->optional()->safeEmail(),
            'status' => ContactStatus::Active,
            'whatsapp_consent' => false,
            'whatsapp_consent_at' => null,
        ];
    }

    /**
     * Indicate that the contact is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContactStatus::Inactive,
        ]);
    }

    /**
     * Indicate that the contact consented to WhatsApp messages.
     */
    public function consented(): static
    {
        return $this->state(fn (array $attributes) => [
            'whatsapp_consent' => true,
            'whatsapp_consent_at' => now(),
        ]);
    }
}
