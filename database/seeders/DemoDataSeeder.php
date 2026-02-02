<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Campaign;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create 10 users
        $users = collect();

        for ($i = 1; $i <= 10; $i++) {
            $users->push(
                User::create([
                    'name' => fake()->name(),
                    'email' => "user{$i}@example.com",
                    'password' => Hash::make('password'),
                ])
            );
        }

        // 2. Create 1000 campaigns
        for ($i = 1; $i <= 1000; $i++) {
            $user = $users->random();

            Campaign::create([
                'user_id' => $user->id,
                'name' => 'Campaign ' . Str::title(fake()->words(3, true)),
                'subject' => fake()->sentence(6),
                'body' => fake()->paragraphs(rand(1, 3), true),
                'status' => 'draft',
            ]);
        }
    }
}
