<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlatformSeeder::class,
            CompanySeeder::class,
        ]);

        $faker = fake('ja_JP');

        User::factory()->create([
            'name' => $faker->name(),
            'email' => $faker->unique()->safeEmail(),
        ]);
    }
}
