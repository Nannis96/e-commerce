<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignItem extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'range',
        'days',
        'price_per_days',
        'subtotal',
        'provider_status',
        'description',
        'campaign_id',
        'media_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_per_days' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Get the campaign that owns the campaign item.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the media that belongs to the campaign item.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
