<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use LaravelInteraction\Support\Interaction;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection|\LaravelInteraction\Rate\Rating[] $ratableRatings
 * @property-read \Illuminate\Database\Eloquent\Collection|\LaravelInteraction\Rate\Concerns\Rater[] $raters
 * @property-read int|null $ratable_ratings_count
 * @property-read int|null $raters_count
 * @property float|null $ratable_ratings_sum_rating
 * @property float|null $ratable_ratings_avg_rating
 *
 * @method static static|\Illuminate\Database\Eloquent\Builder whereRatedBy(\Illuminate\Database\Eloquent\Model $user)
 * @method static static|\Illuminate\Database\Eloquent\Builder whereNotRatedBy(\Illuminate\Database\Eloquent\Model $user)
 * @method static static|\Illuminate\Database\Eloquent\Builder withRatersCount($constraints = null)
 */
trait Ratable
{
    public function isRatedBy(Model $user): bool
    {
        if (! is_a($user, config('rate.models.user'))) {
            return false;
        }

        $ratersLoaded = $this->relationLoaded('raters');

        if ($ratersLoaded) {
            return $this->raters->contains($user);
        }

        return ($this->relationLoaded('ratableRatings') ? $this->ratableRatings : $this->ratableRatings())
            ->where(config('rate.column_names.user_foreign_key'), $user->getKey())
            ->count() > 0;
    }

    public function isNotRatedBy(Model $user): bool
    {
        return ! $this->isRatedBy($user);
    }

    public function ratableRatings(): MorphMany
    {
        return $this->morphMany(config('rate.models.pivot'), 'ratable');
    }

    public function raters(): MorphToMany
    {
        return tap(
            $this->morphToMany(
                config('rate.models.user'),
                'ratable',
                config('rate.models.pivot'),
                null,
                config('rate.column_names.user_foreign_key')
            ),
            static function (MorphToMany $relation): void {
                $relation->distinct($relation->getRelated()->qualifyColumn($relation->getRelatedKeyName()));
            }
        );
    }

    /**
     * @param callable|null $constraints
     *
     * @return $this
     */
    public function loadRatersCount($constraints = null)
    {
        $this->loadCount([
            'raters' => fn ($query) => $this->selectDistinctRatersCount($query, $constraints),
        ]);

        return $this;
    }

    public function ratersCount(): int
    {
        if ($this->raters_count !== null) {
            return (int) $this->raters_count;
        }

        $this->loadRatersCount();

        return (int) $this->raters_count;
    }

    /**
     * @phpstan-param 1|2|3|4 $mode
     *
     * @param array<int, string>|null $divisors
     */
    public function ratersCountForHumans(int $precision = 1, int $mode = PHP_ROUND_HALF_UP, $divisors = null): string
    {
        return Interaction::numberForHumans(
            $this->ratersCount(),
            $precision,
            $mode,
            $divisors ?? config('rate.divisors')
        );
    }

    public function scopeWhereRatedBy(Builder $query, Model $user): Builder
    {
        return $query->whereHas('raters', static fn (Builder $query): Builder => $query->whereKey($user->getKey()));
    }

    public function scopeWhereNotRatedBy(Builder $query, Model $user): Builder
    {
        return $query->whereDoesntHave(
            'raters',
            static fn (Builder $query): Builder => $query->whereKey($user->getKey())
        );
    }

    /**
     * @param callable $constraints
     */
    public function scopeWithRatersCount(Builder $query, $constraints = null): Builder
    {
        return $query->withCount(
            [
                'raters' => fn ($query) => $this->selectDistinctRatersCount($query, $constraints),
            ]
        );
    }

    /**
     * @param callable $constraints
     */
    protected function selectDistinctRatersCount(Builder $query, $constraints = null): Builder
    {
        if ($constraints !== null) {
            $query = $constraints($query);
        }

        $column = $query->getModel()
            ->getQualifiedKeyName();

        return $query->select(DB::raw(sprintf('COUNT(DISTINCT(%s))', $column)));
    }

    public function ratableRatingsCount(): int
    {
        if ($this->ratable_ratings_count !== null) {
            return (int) $this->ratable_ratings_count;
        }

        $this->loadCount('ratableRatings');

        return (int) $this->ratable_ratings_count;
    }

    /**
     * @phpstan-param 1|2|3|4 $mode
     *
     * @param array<int, string>|null $divisors
     */
    public function ratableRatingsCountForHumans(
        int $precision = 1,
        int $mode = PHP_ROUND_HALF_UP,
        $divisors = null
    ): string {
        return Interaction::numberForHumans(
            $this->ratableRatingsCount(),
            $precision,
            $mode,
            $divisors ?? config('rate.divisors')
        );
    }

    public function avgRating(): float
    {
        if (\array_key_exists('ratable_ratings_avg_rating', $this->getAttributes())) {
            return (float) $this->ratable_ratings_avg_rating;
        }

        $this->loadAvg('ratableRatings', 'rating');

        return (float) $this->ratable_ratings_avg_rating;
    }

    public function sumRating(): float
    {
        if (\array_key_exists('ratable_ratings_sum_rating', $this->getAttributes())) {
            return (float) $this->ratable_ratings_sum_rating;
        }

        $this->loadSum('ratableRatings', 'rating');

        return (float) $this->ratable_ratings_sum_rating;
    }

    /**
     * @phpstan-param 1|2|3|4 $mode
     *
     * @param array<int, string>|null $divisors
     */
    public function sumRatingForHumans(int $precision = 1, int $mode = PHP_ROUND_HALF_UP, $divisors = null): string
    {
        return Interaction::numberForHumans(
            $this->sumRating(),
            $precision,
            $mode,
            $divisors ?? config('rate.divisors')
        );
    }

    public function ratingPercent(float|int $max = 5): float
    {
        $quantity = $this->ratableRatingsCount();
        $total = $this->sumRating();

        return $quantity * $max > 0 ? $total / ($quantity * $max / 100) : 0;
    }
}
