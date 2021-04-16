<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use LaravelInteraction\Support\Interaction;
use function is_a;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection|\LaravelInteraction\Rate\Rating[] $ratableRatings
 * @property-read \Illuminate\Database\Eloquent\Collection|\LaravelInteraction\Rate\Concerns\Rater[] $raters
 * @property-read int|null $ratable_ratings_count
 * @property-read int|null $raters_count
 * @property-read float|null $ratable_ratings_sum_rating
 * @property-read float|null $ratable_ratings_avg_rating
 *
 * @method static static|\Illuminate\Database\Eloquent\Builder whereRatedBy(\Illuminate\Database\Eloquent\Model $user)
 * @method static static|\Illuminate\Database\Eloquent\Builder whereNotRatedBy(\Illuminate\Database\Eloquent\Model $user)
 * @method static static|\Illuminate\Database\Eloquent\Builder withRatersCount($constraints = null)
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function ratableRatings(): MorphMany
    {
        return $this->morphMany(config('rate.models.rating'), 'ratable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function raters(): MorphToMany
    {
        return tap(
            $this->morphToMany(
                config('rate.models.user'),
                'ratable',
                config('rate.models.rating'),
                null,
                config('rate.column_names.user_foreign_key')
            ),
            static function (MorphToMany $relation) {
                $relation->distinct($relation->getRelated()->qualifyColumn($relation->getRelatedKeyName()));
            }
        );
    }

    public function loadRatersCount($constraints = null)
    {
        $this->loadCount(
            [
                'raters' => function ($query) use ($constraints) {
                    return $this->selectDistinctRatersCount($query, $constraints);
                },
            ]
        );
    }

    public function ratersCount(): int
    {
        if ($this->raters_count !== null) {
            return (int) $this->raters_count;
        }

        $this->loadRatersCount();

        return (int) $this->raters_count;
    }

    public function ratersCountForHumans($precision = 1, $mode = PHP_ROUND_HALF_UP, $divisors = null): string
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

    public function scopeWithRatersCount(Builder $query, $constraints = null): Builder
    {
        return $query->withCount(
            [
                'raters' => function ($query) use ($constraints) {
                    return $this->selectDistinctRatersCount($query, $constraints);
                },
            ]
        );
    }

    protected function selectDistinctRatersCount(Builder $query, $constraints = null): Builder
    {
        if ($constraints !== null) {
            $query = $constraints($query);
        }

        $column = $query->getModel()
            ->getQualifiedKeyName();

        return $query->select(DB::raw("COUNT(DISTINCT({$column}))"));
    }

    public function ratableRatingsCount(): int
    {
        if ($this->ratable_ratings_count !== null) {
            return (int) $this->ratable_ratings_count;
        }
        $this->loadCount('ratableRatings');

        return (int) $this->ratable_ratings_count;
    }

    public function ratableRatingsCountForHumans($precision = 1, $mode = PHP_ROUND_HALF_UP, $divisors = null): string
    {
        return Interaction::numberForHumans(
            $this->ratableRatingsCount(),
            $precision,
            $mode,
            $divisors ?? config('rate.divisors')
        );
    }

    public function avgRating(): float
    {
        if ($this->ratable_ratings_avg_rating !== null) {
            return (float) $this->ratable_ratings_avg_rating;
        }

        $this->loadAvgRating();

        return (float) $this->ratable_ratings_avg_rating;
    }

    public function loadAvgRating()
    {
        if (method_exists($this, 'loadAvg')) {
            $this->loadAvg('ratableRatings', 'rating');
        } else {
            $this->ratable_ratings_avg_rating = $this->ratableRatings()
                ->avg('rating');
        }

        return $this;
    }

    public function sumRating(): float
    {
        if ($this->ratable_ratings_sum_rating !== null) {
            return (float) $this->ratable_ratings_sum_rating;
        }

        $this->loadSumRating();

        return (float) $this->ratable_ratings_sum_rating;
    }

    public function loadSumRating()
    {
        if (method_exists($this, 'loadSum')) {
            $this->loadSum('ratableRatings', 'rating');
        } else {
            $this->ratable_ratings_sum_rating = $this->ratableRatings()
                ->sum('rating');
        }

        return $this;
    }

    public function sumRatingForHumans($precision = 1, $mode = PHP_ROUND_HALF_UP, $divisors = null): string
    {
        return Interaction::numberForHumans(
            $this->sumRating(),
            $precision,
            $mode,
            $divisors ?? config('rate.divisors')
        );
    }

    public function ratingPercent($max = 5)
    {
        $quantity = $this->ratableRatingsCount();
        $total = $this->sumRating();

        return $quantity * $max > 0 ? $total / ($quantity * $max / 100) : 0;
    }
}
