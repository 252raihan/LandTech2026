<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(GeoSeeder::class);
        $this->call(RoleSeeder::class);

        // Idempotent: keyed on the unique email so re-running the seeder does
        // not violate the users_email_unique constraint.
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Phase 3 synthetic domain data. Runs after roles so the seeded
        // registry officer can be assigned its role, and after geo so plots
        // have a mouza to attach to.
        $this->call(PartySeeder::class);
    }
}
