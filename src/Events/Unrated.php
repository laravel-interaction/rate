<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Events;

use Illuminate\Database\Eloquent\Model;

class Unrated
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $rating;

    /**
     * Liked constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model $rating
     */
    public function __construct(Model $rating)
    {
        $this->rating = $rating;
    }
}
