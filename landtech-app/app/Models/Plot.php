<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Phase 3 — a land plot.
 *
 * SCOPE: no geometry column. GeoJSON/PostGIS storage, spatial indexes and
 * overlap queries are deferred to the later GIS phase.
 */
class Plot extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'mouza_id',
        'khatian_no',
        'plot_no',
        'survey_type',
        'area_decimal',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'area_decimal' => 'decimal:4',
        ];
    }

    public function mouza(): BelongsTo
    {
        return $this->belongsTo(Mouza::class);
    }

    public function ownerships(): HasMany
    {
        return $this->hasMany(PlotOwnership::class);
    }

    /**
     * Ownerships that have not been disposed of (disposed_on IS NULL).
     */
    public function currentOwnerships(): HasMany
    {
        return $this->ownerships()->whereNull('disposed_on');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
