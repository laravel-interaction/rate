<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Tests\Events;

use Illuminate\Support\Facades\Event;
use LaravelInteraction\Rate\Events\Unrated;
use LaravelInteraction\Rate\Tests\Models\Channel;
use LaravelInteraction\Rate\Tests\Models\User;
use LaravelInteraction\Rate\Tests\TestCase;

/**
 * @internal
 */
final class UnratedTest extends TestCase
{
    public function testOnce(): void
    {
        $user = User::query()->create();
        $channel = Channel::query()->create();
        $user->rate($channel);
        Event::fake([Unrated::class]);
        $user->unrate($channel);
        Event::assertDispatchedTimes(Unrated::class);
    }

    public function testTimes(): void
    {
        $user = User::query()->create();
        $channel = Channel::query()->create();
        $user->rate($channel);
        Event::fake([Unrated::class]);
        $user->unrate($channel);
        $user->unrate($channel);
        Event::assertDispatchedTimes(Unrated::class);
    }
}
