<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mouza extends Model
{
    use HasFactory;

    protected $fillable = [
        'upazila_id',
        'code',
        'name_en',
        'name_bn',
        'latitude',
        'longitude',
    ];

    public function upazila(): BelongsTo
    {
        return $this->belongsTo(Upazila::class);
    }
}
