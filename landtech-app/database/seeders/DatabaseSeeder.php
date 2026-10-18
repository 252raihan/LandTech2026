<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Demo accounts created for the presentation Demo Mode, keyed by role.
     *
     * These are DEVELOPMENT-ONLY identities. They are created only in
     * local/testing (see `demoOfficer()` and `demoAccounts()`), so production
     * seeding can never create a privileged demo login.
     *
     * Passwords are NEVER hardcoded: each is derived from
     * `LANDTECH_DEMO_PASSWORD` when set, and otherwise from a random unusable
     * value. Nothing here is printed or exposed by the UI.
     */
    public const DEMO_ACCOUNTS = [
        Role::ADMIN => 'admin.demo@example.test',
        Role::REGISTRY_OFFICER => 'registry.officer@example.test',
        Role::BANK_OFFICER => 'bank.officer@example.test',
        Role::CITIZEN => 'citizen.demo@example.test',
    ];

    /**
     * Seed the application's database.
     *
     * Environment policy (Phase 4 / H1):
     *   local | testing — creates the demo accounts and grants each its role, so
     *                     the officer dashboards are exercisable.
     *   anything else   — reference/domain data only. No demo account is
     *                     created and NO privileged role is ever attached to an
     *                     existing user.
     */
    public function run(): void
    {
        $this->call(GeoSeeder::class);
        $this->call(RoleSeeder::class);

        $demoOfficer = $this->demoAccounts();

        // Phase 3 synthetic domain data. Runs after roles so the demo officer
        // can be attributed, and after geo so plots have a mouza to attach to.
        // The officer is passed in explicitly — this seeder never guesses which
        // user should hold a privileged role.
        $this->call(PartySeeder::class, parameters: [
            'registryOfficer' => $demoOfficer[Role::REGISTRY_OFFICER] ?? null,
        ]);
    }

    /**
     * Resolve the designated demo registry officer, or null when the current
     * environment is not a safe place to create privileged demo accounts.
     *
     * Retained for backwards compatibility with existing callers/tests.
     */
    protected function demoOfficer(): ?User
    {
        return $this->demoAccounts()[Role::REGISTRY_OFFICER] ?? null;
    }

    /**
     * Create the development demo accounts, one per implemented role.
     *
     * Returns an empty array outside local/testing, so production seeding
     * creates no demo login and grants no privileged role to anyone.
     *
     * @return array<string, User>
     */
    protected function demoAccounts(): array
    {
        if (! app()->environment(['local', 'testing'])) {
            return [];
        }

        $roleIds = Role::query()->pluck('id', 'name');
        $accounts = [];

        foreach (self::DEMO_ACCOUNTS as $roleName => $email) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $this->demoName($roleName),
                    'password' => bcrypt((string) env('LANDTECH_DEMO_PASSWORD', str()->random(40))),
                    'email_verified_at' => now(),
                ]
            );

            if (isset($roleIds[$roleName])) {
                // syncWithoutDetaching preserves any other legitimate role grants
                // this account may already hold.
                $user->roles()->syncWithoutDetaching([$roleIds[$roleName]]);
            }

            $accounts[$roleName] = $user;
        }

        return $accounts;
    }

    /**
     * Bilingual-safe display name for a demo account.
     */
    protected function demoName(string $roleName): string
    {
        return match ($roleName) {
            Role::ADMIN => 'Demo Administrator',
            Role::REGISTRY_OFFICER => 'Demo Registry Officer',
            Role::BANK_OFFICER => 'Demo Bank Officer',
            Role::CITIZEN => 'Demo Citizen',
            default => 'Demo User',
        };
    }
}
