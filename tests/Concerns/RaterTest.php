<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Tests\Concerns;

use LaravelInteraction\Rate\Rating;
use LaravelInteraction\Rate\Tests\Models\Channel;
use LaravelInteraction\Rate\Tests\Models\User;
use LaravelInteraction\Rate\Tests\TestCase;

/**
 * @internal
 */
final class RaterTest extends TestCase
{
    public function testRate(): void
    {
        $user = User::query()->create();
        $channel = Channel::query()->create();
        $user->rate($channel);
        $this->assertDatabaseHas(
            Rating::query()->getModel()->getTable(),
            [
                'user_id' => $user->getKey(),
                'ratable_type' => $channel->getMorphClass(),
                'ratable_id' => $channel->getKey(),
            ]
        );
        $user->load('raterRatings');
        $user->unrate($channel);
        $user->load('raterRatings');
        $user->rate($channel);
        $user->load('raterRatings');
        $user->rateOnce($channel);
    }

    public function testUnrate(): void
    {
        $user = User::query()->create();
        $channel = Channel::query()->create();
        $user->rate($channel);
        $this->assertDatabaseHas(
            Rating::query()->getModel()->getTable(),
            [
                'user_id' => $user->getKey(),
                'ratable_type' => $channel->getMorphClass(),
                'ratable_id' => $channel->getKey(),
            ]
        );
        $user->rate($channel);
        $user->unrate($channel);
        $this->assertDatabaseMissing(
            Rating::query()->getModel()->getTable(),
            [
                'user_id' => $user->getKey(),
                'ratable_type' => $channel->getMorphClass(),
                'ratable_id' => $channel->getKey(),
            ]
        );
    }

    public function testRatings(): void
    {
        $user = User::query()->create();
        $channel = Channel::query()->create();
        $user->rate($channel);
        $this->assertSame(1, $user->raterRatings()->count());
        $this->assertSame(1, $user->raterRatings->count());
    }

    public function testHasRated(): void
    {
        $user = User::query()->create();
        $channel = Channel::query()->create();
        $user->rate($channel);
        $this->assertTrue($user->hasRated($channel));
        $user->unrate($channel);
        $user->load('raterRatings');
        $this->assertFalse($user->hasRated($channel));
    }

    public function testHasNotRated(): void
    {
        $user = User::query()->create();
        $channel = Channel::query()->create();
        $user->rate($channel);
        $this->assertFalse($user->hasNotRated($channel));
        $user->unrate($channel);
        $this->assertTrue($user->hasNotRated($channel));
    }
}
