<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Phase 3 — normalized many-to-many roles (roles + role_user pivot).
     *
     * The `users` table itself is untouched; authorization data lives entirely
     * in the pivot, so Phase 2's auth schema stays exactly as committed.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Phase 5 — claims linking this account to a Party identity.
     *
     * Only rows with status = verified confer any authority over that party's
     * land. See PartyUserLink and PartyPolicy::owns().
     */
    public function partyLinks(): HasMany
    {
        return $this->hasMany(PartyUserLink::class);
    }

    /**
     * Verified party links only. This is the authority source for ownership.
     */
    public function verifiedPartyLinks(): HasMany
    {
        return $this->partyLinks()->where('status', PartyUserLink::STATUS_VERIFIED);
    }

    /**
     * Party IDs this user has a VERIFIED link to. Empty until a reviewer
     * approves a link — which is what prevents claiming land by assertion.
     *
     * @return list<int>
     */
    public function verifiedPartyIds(): array
    {
        return $this->verifiedPartyLinks()->pluck('party_id')->all();
    }

    public function hasVerifiedPartyLink(): bool
    {
        return $this->verifiedPartyLinks()->exists();
    }

    // ---- Phase 5 citizen panel relations ----

    public function landRecords(): HasMany
    {
        return $this->hasMany(LandRecord::class);
    }

    public function landDocuments(): HasMany
    {
        return $this->hasMany(LandDocument::class);
    }

    public function saleListings(): HasMany
    {
        return $this->hasMany(SaleListing::class);
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class);
    }

    public function buyerInterestRequests(): HasMany
    {
        return $this->hasMany(BuyerInterestRequest::class);
    }

    /**
     * True when the user holds ANY of the given role names.
     */
    public function hasRole(string ...$names): bool
    {
        return $this->roles()->whereIn('name', $names)->exists();
    }

    public function hasAnyRole(array $names): bool
    {
        return $this->roles()->whereIn('name', $names)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }
}
