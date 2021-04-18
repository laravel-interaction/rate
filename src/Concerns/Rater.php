<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use LaravelInteraction\Rate\Rating;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection|\LaravelInteraction\Rate\Rating[] $raterRatings
 * @property-read int|null $rater_ratings_count
 */
trait Rater
{
    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     * @param mixed $value
     *
     * @return \LaravelInteraction\Rate\Rating
     */
    public function rate(Model $object, $value = 1): Rating
    {
        $raterRatingsLoaded = $this->relationLoaded('raterRatings');
        if ($raterRatingsLoaded) {
            $this->unsetRelation('raterRatings');
        }

        return $this->raterRatings()
            ->create([
                'ratable_id' => $object->getKey(),
                'ratable_type' => $object->getMorphClass(),
                'rating' => $value,
            ]);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     * @param mixed $value
     *
     * @return \LaravelInteraction\Rate\Rating
     */
    public function rateOnce(Model $object, $value = 1): Rating
    {
        $attributes = [
            'ratable_id' => $object->getKey(),
            'ratable_type' => $object->getMorphClass(),
        ];

        $values = [
            'rating' => $value,
        ];
        $rating = $this->raterRatings()
            ->where($attributes)
            ->firstOrNew($attributes, $values);
        $rating->fill($values);
        if ($rating->isDirty() || ! $rating->exists) {
            $raterRatingsLoaded = $this->relationLoaded('raterRatings');
            if ($raterRatingsLoaded) {
                $this->unsetRelation('raterRatings');
            }
            $rating->save();
        }

        return $rating;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $object
     *
     * @return bool
     */
    public function unrate(Model $object): bool
    {
        $hasNotRated = $this->hasNotRated($object);
        if ($hasNotRated) {
            return true;
        }
        $raterRatingsLoaded = $this->relationLoaded('raterRatings');
        if ($raterRatingsLoaded) {
            $this->unsetRelation('raterRatings');
        }

        return (bool) $this->ratedItems(get_class($object))
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
