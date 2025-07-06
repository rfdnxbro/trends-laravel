<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Platform>
 */
class PlatformFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $platform = $this->faker->company();

        return [
            'name' => $platform.' '.$this->faker->randomNumber(4),
            'base_url' => $this->faker->url(),
            'is_active' => true,
        ];
    }
}
