<?php

namespace Database\Seeders;

use App\Models\Link;
use App\Models\User;
use App\Services\ShortCodeGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed a ready-to-use demo account with a couple of links and clicks,
     * so the app can be explored without registering first.
     *
     * Idempotent: safe to run multiple times.
     */
    public function run(): void
    {
        $demo = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
            ],
        );

        $generator = new ShortCodeGenerator();

        $urls = [
            'https://laravel.com/docs',
            'https://filamentphp.com',
            'https://frankenphp.dev',
        ];

        foreach ($urls as $url) {
            $link = Link::firstOrCreate(
                ['user_id' => $demo->id, 'original_url' => $url],
                ['code' => $generator->generateUnique()],
            );

            // Add a few sample clicks for the statistics screen.
            if ($link->clicks()->count() === 0) {
                Link::withoutEvents(function () use ($link) {
                    $link->clicks()->createMany([
                        ['ip_address' => '203.0.113.10', 'user_agent' => 'Mozilla/5.0', 'created_at' => now()->subDays(2)],
                        ['ip_address' => '198.51.100.4', 'user_agent' => 'curl/8.0', 'created_at' => now()->subDay()],
                    ]);
                });

                $link->update(['clicks_count' => $link->clicks()->count()]);
            }
        }

        $this->command?->info('Demo account: demo@example.com / password');
    }
}
