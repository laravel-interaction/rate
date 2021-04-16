<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection|\LaravelInteraction\Rate\Rating[] $raterRatings
 * @property-read int|null $rater_ratings_count
 */
trait Rater
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     * @param mixed $value
     */
    public function rate(Model $object, $value = 1): void
    {
        $this->ratedItems(get_class($object))
            ->attach($object->getKey(), [
                'rating' => $value,
            ]);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     * @param mixed $value
     */
    public function rateOnce(Model $object, $value = 1): void
    {
        $rating = ($this->relationLoaded('raterRatings') ? $this->raterRatings : $this->raterRatings())
            ->where('ratable_id', $object->getKey())
            ->where('ratable_type', $object->getMorphClass())
            ->first();
        if ($rating !== null) {
            $rating->rating = $value;
            $rating->save();

            return;
        }

        $this->ratedItems(get_class($object))
            ->attach($object->getKey(), [
                'rating' => $value,
            ]);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     */
    public function unrate(Model $object): void
    {
        $hasNotRated = $this->hasNotRated($object);
        if ($hasNotRated) {
            return;
        }

        $this->ratedItems(get_class($object))
            ->detach($object->getKey());
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     *
     * @return bool
     */
    public function hasRated(Model $object): bool
    {
        return ($this->relationLoaded('raterRatings') ? $this->raterRatings : $this->raterRatings())
            ->where('ratable_id', $object->getKey())
            ->where('ratable_type', $object->getMorphClass())
            ->count() > 0;
    }

    public function hasNotRated(Model $object): bool
    {
        return ! $this->hasRated($object);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function raterRatings(): HasMany
    {
        return $this->hasMany(
            config('rate.models.rating'),
            config('rate.column_names.user_foreign_key'),
            $this->getKeyName()
        );
    }

    /**
     * @param string $class
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    protected function ratedItems(string $class): MorphToMany
    {
        return $this->morphedByMany(
            $class,
            'ratable',
            config('rate.models.rating'),
            config('rate.column_names.user_foreign_key')
        )
            ->withPivot('rating')
            ->withTimestamps();
    }
}
