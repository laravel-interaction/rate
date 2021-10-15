<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Tests\Configuration;

use LaravelInteraction\Rate\Rating;
use LaravelInteraction\Rate\Tests\Models\Channel;
use LaravelInteraction\Rate\Tests\Models\User;
use LaravelInteraction\Rate\Tests\TestCase;

/**
 * @internal
 */
final class UuidsTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        config([
            'rate.uuids' => true,
        ]);
    }

    public function testKeyType(): void
    {
        $rating = new Rating();
        self::assertSame('string', $rating->getKeyType());
    }

    public function testIncrementing(): void
    {
        $rating = new Rating();
        self::assertFalse($rating->getIncrementing());
    }

    public function testKeyName(): void
    {
        $rating = new Rating();
        self::assertSame('uuid', $rating->getKeyName());
    }

    public function testKey(): void
    {
        $user = User::query()->create();
        $channel = Channel::query()->create();
        $user->rate($channel);
        self::assertIsString($user->raterRatings()->firstOrFail()->getKey());
    }
}
