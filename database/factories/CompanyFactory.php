<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * モデルのデフォルト状態を定義
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companyName = $this->faker->company();
        $domain = strtolower(str_replace([' ', '.', ','], ['', '', ''], $companyName)).'.com';

        return [
            'name' => $companyName,
            'domain' => $this->faker->unique()->domainName(),
            'description' => $this->faker->paragraph(),
            'logo_url' => $this->faker->imageUrl(200, 200),
            'website_url' => 'https://'.$domain,
            'is_active' => true,
        ];
    }

    /**
     * 非アクティブな企業を作成
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * リアルなデータでテック企業を作成
     */
    public function techCompany(): static
    {
        $techNames = [
            'Tech Solutions Inc.',
            'Digital Innovation Co.',
            'Cloud Systems Ltd.',
            'Data Analytics Corp.',
            'AI Technologies Inc.',
            'Software Development Co.',
            'Cyber Security Solutions',
            'Mobile Apps Inc.',
            'Web Development Co.',
            'DevOps Solutions Ltd.',
        ];

        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement($techNames),
            'description' => $this->faker->randomElement([
                'Leading provider of cloud-based solutions for enterprise customers.',
                'Innovative software development company specializing in AI and machine learning.',
                'Expert team delivering cutting-edge web and mobile applications.',
                'Cybersecurity solutions for modern digital infrastructure.',
                'Data analytics and business intelligence platform provider.',
            ]),
        ]);
    }

    /**
     * Create a company with specific domain.
     */
    public function withDomain(string $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => $domain,
            'website_url' => 'https://'.$domain,
        ]);
    }

    /**
     * アクティブな企業を作成
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
