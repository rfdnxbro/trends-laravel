<?php

namespace Database\Seeders;

use App\Models\Platform;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $platforms = [
            [
                'name' => 'はてなブックマーク',
                'base_url' => 'https://b.hatena.ne.jp/hotentry/it',
                'is_active' => true,
            ],
            [
                'name' => 'Qiita',
                'base_url' => 'https://qiita.com/trend',
                'is_active' => true,
            ],
            [
                'name' => 'Zenn',
                'base_url' => 'https://zenn.dev/',
                'is_active' => true,
            ],
        ];

        foreach ($platforms as $platform) {
            Platform::firstOrCreate(
                ['name' => $platform['name']],
                $platform
            );
        }
    }
}
