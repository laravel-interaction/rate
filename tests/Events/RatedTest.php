<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Tests\Events;

use Illuminate\Support\Facades\Event;
use LaravelInteraction\Rate\Events\Rated;
use LaravelInteraction\Rate\Tests\Models\Channel;
use LaravelInteraction\Rate\Tests\Models\User;
use LaravelInteraction\Rate\Tests\TestCase;

class RatedTest extends TestCase
{
    public function testOnce(): void
    {
        $user = User::query()->create();
        $channel = Channel::query()->create();
        Event::fake([Rated::class]);
        $user->rate($channel);
        Event::assertDispatchedTimes(Rated::class);
    }

    public function testTimes(): void
    {
        $user = User::query()->create();
        $channel = Channel::query()->create();
        Event::fake([Rated::class]);
        $user->rate($channel);
        $user->rate($channel);
        $user->rate($channel);
        Event::assertDispatchedTimes(Rated::class, 3);
    }

    public function testRateOnceTimes(): void
    {
        $user = User::query()->create();
        $channel = Channel::query()->create();
        Event::fake([Rated::class]);
        $user->rateOnce($channel);
        $user->rateOnce($channel);
        $user->rateOnce($channel);
        Event::assertDispatchedTimes(Rated::class);
    }
}
