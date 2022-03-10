<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use LaravelInteraction\Rate\Events\Rated;
use LaravelInteraction\Rate\Events\Rerated;
use LaravelInteraction\Rate\Events\Unrated;

/**
 * @property float $rating
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Database\Eloquent\Model $user
 * @property \Illuminate\Database\Eloquent\Model $rater
 * @property \Illuminate\Database\Eloquent\Model $ratable
 *
 * @method static \LaravelInteraction\Rate\Rating|\Illuminate\Database\Eloquent\Builder withType(string $type)
 * @method static \LaravelInteraction\Rate\Rating|\Illuminate\Database\Eloquent\Builder query()
 */
class Rating extends MorphPivot
{
    protected function uuids(): bool
    {
        return (bool) config('rate.uuids');
    }

    /**
     * @var bool
     */
    public $incrementing = true;

    public function getIncrementing(): bool
    {
        if ($this->uuids()) {
            return false;
        }

        return parent::getIncrementing();
    }

    public function getKeyName(): string
    {
        return $this->uuids() ? 'uuid' : parent::getKeyName();
    }

    public function getKeyType(): string
    {
        return $this->uuids() ? 'string' : parent::getKeyType();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(
            function (self $like): void {
                if ($like->uuids()) {
                    $like->{$like->getKeyName()} = Str::orderedUuid();
                }
            }
        );
    }

    /**
     * @var array<string, class-string<\LaravelInteraction\Rate\Events\Rated>>|array<string, class-string<\LaravelInteraction\Rate\Events\Rerated>>|array<string, class-string<\LaravelInteraction\Rate\Events\Unrated>>
     */
    protected $dispatchesEvents = [
        'created' => Rated::class,
        'updated' => Rerated::class,
        'deleted' => Unrated::class,
    ];

    public function getTable(): string
    {
        return config('rate.table_names.pivot') ?: parent::getTable();
    }

    public function ratable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('rate.models.user'), config('rate.column_names.user_foreign_key'));
    }

    public function rater(): BelongsTo
    {
        return $this->user();
    }

    public function isRatedBy(Model $user): bool
    {
        return $user->is($this->rater);
    }

    public function isRatedTo(Model $object): bool
    {
        return $object->is($this->ratable);
    }

    public function scopeWithType(Builder $query, string $type): Builder
    {
        return $query->where('ratable_type', app($type)->getMorphClass());
    }
}
