<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'domain' => $this->faker->domainName(),
            'description' => $this->faker->paragraph(),
            'logo_url' => $this->faker->imageUrl(200, 200),
            'website_url' => $this->faker->url(),
            'is_active' => true,
        ];
    }
}
