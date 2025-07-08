<?php

namespace Database\Seeders;

use App\Constants\Platform as PlatformConstants;
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
                'name' => PlatformConstants::HATENA_BOOKMARK,
                'base_url' => 'https://b.hatena.ne.jp/hotentry/it',
                'is_active' => true,
            ],
            [
                'name' => PlatformConstants::QIITA,
                'base_url' => 'https://qiita.com/trend',
                'is_active' => true,
            ],
            [
                'name' => PlatformConstants::ZENN,
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
