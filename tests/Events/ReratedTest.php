<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Tests\Events;

use Illuminate\Support\Facades\Event;
use LaravelInteraction\Rate\Events\Rerated;
use LaravelInteraction\Rate\Tests\Models\Channel;
use LaravelInteraction\Rate\Tests\Models\User;
use LaravelInteraction\Rate\Tests\TestCase;

/**
 * @internal
 */
final class ReratedTest extends TestCase
{
    public function testOnce(): void
    {
        $user = User::query()->create();
        $channel = Channel::query()->create();
        Event::fake([Rerated::class]);
        $user->rateOnce($channel);
        $user->rateOnce($channel, 2);
        Event::assertDispatchedTimes(Rerated::class);
    }

    public function testTimes(): void
    {
        $user = User::query()->create();
        $channel = Channel::query()->create();
        Event::fake([Rerated::class]);
        $user->rateOnce($channel);
        $user->rateOnce($channel, 2);
        $user->rateOnce($channel, 3);
        Event::assertDispatchedTimes(Rerated::class, 2);
    }
}
