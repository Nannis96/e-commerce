<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaImage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'route',
        'media_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the media that owns the image.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
