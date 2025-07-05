<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = fake('ja_JP');
        
        $realCompanies = [
            [
                'name' => $faker->company(),
                'domain' => $faker->domainName(),
                'description' => $faker->realText(100),
                'website_url' => $faker->url(),
                'is_active' => $faker->boolean(95),
            ],
            [
                'name' => $faker->company(),
                'domain' => $faker->unique()->domainName(),
                'description' => $faker->realText(120),
                'website_url' => $faker->url(),
                'is_active' => $faker->boolean(95),
            ],
            [
                'name' => $faker->company(),
                'domain' => $faker->unique()->domainName(),
                'description' => $faker->realText(80),
                'website_url' => $faker->url(),
                'is_active' => $faker->boolean(95),
            ],
            [
                'name' => $faker->company(),
                'domain' => $faker->unique()->domainName(),
                'description' => $faker->realText(150),
                'website_url' => $faker->url(),
                'is_active' => $faker->boolean(95),
            ],
            [
                'name' => $faker->company(),
                'domain' => $faker->unique()->domainName(),
                'description' => $faker->realText(90),
                'website_url' => $faker->url(),
                'is_active' => $faker->boolean(95),
            ],
        ];

        foreach ($realCompanies as $company) {
            Company::firstOrCreate(
                ['domain' => $company['domain']],
                $company
            );
        }

        for ($i = 0; $i < 20; $i++) {
            $companyName = $faker->company();
            $domain = $faker->unique()->domainName();
            
            Company::firstOrCreate(
                ['domain' => $domain],
                [
                    'name' => $companyName,
                    'domain' => $domain,
                    'description' => $faker->realText($faker->numberBetween(50, 200)),
                    'website_url' => $faker->url(),
                    'is_active' => $faker->boolean(85),
                ]
            );
        }
    }
}
