<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name_en',
        'name_bn',
        'division_en',
        'division_bn',
        'latitude',
        'longitude',
    ];

    public function upazilas(): HasMany
    {
        return $this->hasMany(Upazila::class);
    }
}
