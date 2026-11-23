<?php

namespace Database\Factories;

use App\Enums\ObligationPaymentStatus;
use App\Models\Contact;
use App\Models\Obligation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Obligation>
 */
class ObligationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'title' => fake()->sentence(3),
            'notes' => fake()->optional()->sentence(),
            'due_on' => fake()->date(),
            'payment_status' => ObligationPaymentStatus::Pending,
        ];
    }

    /**
     * Indicate that the obligation is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => ObligationPaymentStatus::Paid,
        ]);
    }

    /**
     * Indicate that the obligation is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => ObligationPaymentStatus::Cancelled,
        ]);
    }
}
