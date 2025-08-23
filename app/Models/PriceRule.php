<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'start_date',
        'end_date',
        'name',
        'value_pct'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'value_pct' => 'integer'
    ];

    protected $dates = ['deleted_at'];

    // Relaciones
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'media_price_rules')
                    ->withTimestamps()
                    ->withPivot('id')
                    ->wherePivotNull('deleted_at');
    }

    public function mediaPriceRules()
    {
        return $this->hasMany(MediaPriceRule::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
    }
}
