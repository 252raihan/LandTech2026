<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Phase 3 — the four approved roles.
 *
 * Labels are bilingual to match the en/bn UI (Phase 1 localization).
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => Role::ADMIN,
                'label_en' => 'Administrator',
                'label_bn' => 'প্রশাসক',
            ],
            [
                'name' => Role::REGISTRY_OFFICER,
                'label_en' => 'Registry Officer',
                'label_bn' => 'রেজিস্ট্রি কর্মকর্তা',
            ],
            [
                'name' => Role::BANK_OFFICER,
                'label_en' => 'Bank Officer',
                'label_bn' => 'ব্যাংক কর্মকর্তা',
            ],
            [
                'name' => Role::CITIZEN,
                'label_en' => 'Citizen',
                'label_bn' => 'নাগরিক',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
