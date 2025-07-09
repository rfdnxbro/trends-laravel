<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Platform>
 */
class PlatformFactory extends Factory
{
    /**
     * モデルのデフォルト状態を定義
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
