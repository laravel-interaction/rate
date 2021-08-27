<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Events;

use Illuminate\Database\Eloquent\Model;

class Rated
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $rating;

    public function __construct(Model $rating)
    {
        $this->rating = $rating;
    }
}
