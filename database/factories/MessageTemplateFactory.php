<?php

namespace Database\Factories;

use App\Enums\MessageTemplateStatus;
use App\Models\MessageTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageTemplate>
 */
class MessageTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->lexify('Modelo ??????'),
            'body' => 'Olá, {{nome}}. '.fake()->sentence(),
            'status' => MessageTemplateStatus::Active,
            'is_default' => false,
            'meta_template_name' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (MessageTemplate $template): void {
            $template->is_default = false;
            $template->meta_template_name = null;
        });
    }

    /**
     * Indicate that the template is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MessageTemplateStatus::Inactive,
        ]);
    }
}
