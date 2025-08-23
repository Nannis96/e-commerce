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
        'period_limit',
        'price_limit',
        'status',
        'user_id',
        'cancellation_id',
    ];

    protected $casts = [
        'price_limit' => 'decimal:2',
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

    /**
     * Get active price rules for the media.
     */
    public function activePriceRules(): BelongsToMany
    {
        return $this->priceRules()
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    /**
     * Get the cancellation policy for the media.
     */
    public function cancellation(): BelongsTo
    {
        return $this->belongsTo(Cancellation::class);
    }

    /**
     * Get all campaign items for the media.
     */
    public function campaignItems(): HasMany
    {
        return $this->hasMany(CampaignItem::class);
    }
}
