<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Phase 3 — the ownership record linking a Party to a Plot.
 *
 * SHARES ARE EXACT FRACTIONS. 1/3 is stored as numerator=1, denominator=3 —
 * never 0.3333. This keeps fractional co-ownership sums and future Faraz /
 * inheritance arithmetic lossless.
 *
 * `disposed_on` NULL means the ownership is current. Historical records are kept
 * (not deleted) because later phases need the full ownership chain.
 */
class PlotOwnership extends Model
{
    use HasFactory, SoftDeletes;

    public const ROLE_SOLE = 'sole';
    public const ROLE_JOINT = 'joint';
    public const ROLE_HEIR_SHARE = 'heir_share';

    public const STATUS_CURRENT = 'current';
    public const STATUS_HISTORICAL = 'historical';
    public const STATUS_DISPUTED = 'disputed';

    protected $fillable = [
        'plot_id',
        'party_id',
        'ownership_role',
        'share_numerator',
        'share_denominator',
        'acquired_on',
        'disposed_on',
        'source_deed_no',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'share_numerator' => 'integer',
            'share_denominator' => 'integer',
            'acquired_on' => 'date',
            'disposed_on' => 'date',
        ];
    }

    public function plot(): BelongsTo
    {
        return $this->belongsTo(Plot::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /**
     * The share as a float, for display only.
     * Never use this for arithmetic — use the exact fraction.
     */
    public function shareRatio(): float
    {
        if ($this->share_denominator === 0) {
            return 0.0;
        }

        return $this->share_numerator / $this->share_denominator;
    }

    /**
     * Exact share in lowest terms, e.g. "1/3".
     */
    public function shareLabel(): string
    {
        return $this->share_numerator . '/' . $this->share_denominator;
    }

    public function isCurrent(): bool
    {
        return $this->disposed_on === null;
    }

    public function scopeCurrent($query)
    {
        return $query->whereNull('disposed_on');
    }
}
