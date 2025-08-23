<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_name',
        'tax',
        'commission',
        'bank_account',
        'clabe',
        'user_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'commission' => 'integer',
    ];

    /**
     * Get the user that owns the provider.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all payouts for this provider.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'user_id', 'user_id');
    }

    /**
     * Get all media owned by this provider.
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'user_id', 'user_id');
    }
}
