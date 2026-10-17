<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Phase 3 role. Seeded with exactly four roles:
 * admin, registry_officer, bank_officer, citizen.
 *
 * Deliberately hand-rolled instead of Spatie Permission (out of scope this phase).
 */
class Role extends Model
{
    use HasFactory;

    public const ADMIN = 'admin';
    public const REGISTRY_OFFICER = 'registry_officer';
    public const BANK_OFFICER = 'bank_officer';
    public const CITIZEN = 'citizen';

    protected $fillable = [
        'name',
        'label_en',
        'label_bn',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
