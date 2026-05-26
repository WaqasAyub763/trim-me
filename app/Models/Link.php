<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $original_url
 * @property string $short_code
 * @property int $click_count
 * @property \Carbon\CarbonImmutable|null $expires_at
 * @property \Carbon\CarbonImmutable $created_at
 * @property \Carbon\CarbonImmutable $updated_at
 */
class Link extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_url',
        'short_code',
        'click_count',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
        'click_count' => 'integer',
    ];

    public function clickLogs(): HasMany
    {
        return $this->hasMany(ClickLog::class);
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', CarbonImmutable::now());
        });
    }

    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->expires_at !== null
                && $this->expires_at->isPast(),
        );
    }

    public function getRouteKeyName(): string
    {
        return 'short_code';
    }
}
