<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cancellation extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'start_days',
        'end_days', 
        'commission'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_days' => 'integer',
        'end_days' => 'integer',
        'commission' => 'integer',
    ];

    /**
     * Get all media that use this cancellation policy.
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }
}
