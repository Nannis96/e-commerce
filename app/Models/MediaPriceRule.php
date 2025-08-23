<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaPriceRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'media_id',
        'price_rule_id'
    ];

    protected $dates = ['deleted_at'];

    /**
     * Get the media that owns the media price rule.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * Get the price rule that owns the media price rule.
     */
    public function priceRule(): BelongsTo
    {
        return $this->belongsTo(PriceRule::class);
    }
}
