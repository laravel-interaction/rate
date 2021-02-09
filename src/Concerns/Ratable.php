<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use function is_a;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection|\LaravelInteraction\Rate\Rating[] $ratableRatings
 * @property-read \Illuminate\Database\Eloquent\Collection|\LaravelInteraction\Rate\Concerns\Rater[] $raters
 * @property-read int|null $raters_count
 *
 * @method static static|\Illuminate\Database\Eloquent\Builder whereRatedBy(\Illuminate\Database\Eloquent\Model $user)
 * @method static static|\Illuminate\Database\Eloquent\Builder whereNotRatedBy(\Illuminate\Database\Eloquent\Model $user)
 */
trait Ratable
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $user
     *
     * @return bool
     */
    public function isRatedBy(Model $user): bool
    {
        if (! is_a($user, config('rate.models.user'))) {
            return false;
        }

        if ($this->relationLoaded('raters')) {
            return $this->raters->contains($user);
        }

        return ($this->relationLoaded('ratableRatings') ? $this->ratableRatings : $this->ratableRatings())
            ->where(config('rate.column_names.user_foreign_key'), $user->getKey())->count() > 0;
    }

    public function isNotRatedBy(Model $user): bool
    {
        return ! $this->isRatedBy($user);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function ratableRatings(): MorphMany
    {
        return $this->morphMany(config('rate.models.rating'), 'ratable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function raters(): BelongsToMany
    {
        return $this->morphToMany(
            config('rate.models.user'),
            'ratable',
            config('rate.models.rating'),
            null,
            config('rate.column_names.user_foreign_key')
        )->withTimestamps();
    }

    public function ratersCount(): int
    {
        if ($this->raters_count !== null) {
            return (int) $this->raters_count;
        }

        $this->loadCount('raters');

        return (int) $this->raters_count;
    }

    public function ratersCountForHumans($precision = 1, $mode = PHP_ROUND_HALF_UP, $divisors = null): string
    {
        $number = $this->ratersCount();
        $divisors = collect($divisors ?? config('rate.divisors'));
        $divisor = $divisors->keys()->filter(
            function ($divisor) use ($number) {
                return $divisor <= abs($number);
            }
        )->last(null, 1);

        if ($divisor === 1) {
            return (string) $number;
        }

        return number_format(round($number / $divisor, $precision, $mode), $precision) . $divisors->get($divisor);
    }

    public function scopeWhereRatedBy(Builder $query, Model $user): Builder
    {
        return $query->whereHas(
            'raters',
            function (Builder $query) use ($user) {
                return $query->whereKey($user->getKey());
            }
        );
    }

    public function scopeWhereNotRatedBy(Builder $query, Model $user): Builder
    {
        return $query->whereDoesntHave(
            'raters',
            function (Builder $query) use ($user) {
                return $query->whereKey($user->getKey());
            }
        );
    }
}
