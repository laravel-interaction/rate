<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Events;

use Illuminate\Database\Eloquent\Model;

class Rerated
{
    public function __construct(public Model $model)
    {
    }
}
