<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Tests\Concerns;

use LaravelInteraction\Rate\Rating;
use LaravelInteraction\Rate\Tests\Models\Channel;
use LaravelInteraction\Rate\Tests\Models\User;
use LaravelInteraction\Rate\Tests\TestCase;

class RaterTest extends TestCase
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

    public function testToggleRate(): void
    {
        $user = User::query()->create();
        $channel = Channel::query()->create();
        $user->toggleRate($channel);
        $this->assertDatabaseHas(
            Rating::query()->getModel()->getTable(),
            [
                'user_id' => $user->getKey(),
                'ratable_type' => $channel->getMorphClass(),
                'ratable_id' => $channel->getKey(),
            ]
        );
        $user->toggleRate($channel);
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
        $user->toggleRate($channel);
        self::assertSame(1, $user->raterRatings()->count());
        self::assertSame(1, $user->raterRatings->count());
    }

    public function testHasRated(): void
    {
        $user = User::query()->create();
        $channel = Channel::query()->create();
        $user->toggleRate($channel);
        self::assertTrue($user->hasRated($channel));
        $user->toggleRate($channel);
        $user->load('raterRatings');
        self::assertFalse($user->hasRated($channel));
    }

    public function testHasNotRated(): void
    {
        $user = User::query()->create();
        $channel = Channel::query()->create();
        $user->toggleRate($channel);
        self::assertFalse($user->hasNotRated($channel));
        $user->toggleRate($channel);
        self::assertTrue($user->hasNotRated($channel));
    }
}
