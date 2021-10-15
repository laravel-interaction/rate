<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate;

use LaravelInteraction\Support\InteractionList;
use LaravelInteraction\Support\InteractionServiceProvider;

class RateServiceProvider extends InteractionServiceProvider
{
    /**
     * @var string
     */
    protected $interaction = InteractionList::RATE;
}
