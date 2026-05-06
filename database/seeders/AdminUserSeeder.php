<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin local uniquement — en S3+, l'admin réel sera créé via magic link
        // sur le domaine de prod, jamais via seeder en environnement live.
        if (! app()->environment('local', 'testing')) {
            return;
        }

        User::updateOrCreate(
            ['email' => 'admin@bia-namur.test'],
            [
                'name' => 'Admin local',
                'password' => Hash::make('changemenow'),    // mdp local jetable, sera retire en J3 magic link
                'role' => User::ROLE_ADMIN,
                'locale' => 'fr',
                'subscription_tier' => User::TIER_FREE,
                'email_verified_at' => now(),
            ],
        );
    }
}
