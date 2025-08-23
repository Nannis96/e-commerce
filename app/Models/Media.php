<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'location',
        'price_per_day',
        'status',
        'active',
        'user_id',
    ];

    protected $casts = [
        'price_per_day' => 'decimal:2',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the images for the media.
     */
    public function images(): HasMany
    {
        return $this->hasMany(MediaImage::class);
    }

    /**
     * Get the user that owns the media.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the price rules for the media.
     */
    public function priceRules(): BelongsToMany
    {
        return $this->belongsToMany(PriceRule::class, 'media_price_rules')
                    ->withTimestamps()
                    ->withPivot('id')
                    ->wherePivotNull('deleted_at');
    }

    /**
     * Get all price rules including soft deleted ones.
     */
    public function allPriceRules(): BelongsToMany
    {
        return $this->belongsToMany(PriceRule::class, 'media_price_rules')
                    ->withTimestamps()
                    ->withPivot('id', 'deleted_at');
    }

    public function activePriceRules(): BelongsToMany
    {
        return $this->priceRules()
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    public function campaignItems(): HasMany
    {
        return $this->hasMany(CampaignItem::class);
    }
}
