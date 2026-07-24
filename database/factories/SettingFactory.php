<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use NuzulFikrieCoder\LaravelMailmanager\Enums\SettingType;
use NuzulFikrieCoder\LaravelMailmanager\Models\Setting;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group' => 'mail',
            'key' => fake()->unique()->slug(2),
            'value' => fake()->word(),
            'type' => SettingType::String,
            'is_encrypted' => false,
        ];
    }

    public function encrypted(): static
    {
        return $this->state(fn (): array => [
            'is_encrypted' => true,
            'type' => SettingType::Encrypted,
        ]);
    }
}
