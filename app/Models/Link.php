<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

class Link extends Model
{
    /** @use HasFactory<\Database\Factories\LinkFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_url',
        'code',
        'clicks_count',
    ];

    protected $casts = [
        'clicks_count' => 'integer',
    ];

    /**
     * @return BelongsTo<User, Link>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Click>
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    /**
     * Full short URL for this link, e.g. http://localhost:8080/abc123
     */
    public function getShortUrlAttribute(): string
    {
        return url($this->code);
    }

    /**
     * Record a visit: store a Click (IP + timestamp) and increment the counter.
     */
    public function registerClick(Request $request): Click
    {
        $click = $this->clicks()->create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        $this->increment('clicks_count');

        return $click;
    }
}
