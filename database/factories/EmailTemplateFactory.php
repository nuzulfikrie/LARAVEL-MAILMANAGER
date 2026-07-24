<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use NuzulFikrieCoder\LaravelMailmanager\Enums\TemplateStatus;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->sentence(3);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->optional()->sentence(),
            'subject' => 'Hello {name}',
            'design_json' => ['body' => ['rows' => []]],
            'html_content' => '<p>Hello {name}</p>',
            'parameters' => [
                'name' => [
                    'type' => 'string',
                    'required' => true,
                ],
            ],
            'status' => TemplateStatus::Draft,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => TemplateStatus::Active,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => TemplateStatus::Inactive,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => TemplateStatus::Archived,
        ]);
    }
}
