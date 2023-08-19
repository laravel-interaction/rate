<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Tests;

use Illuminate\Support\Carbon;
use LaravelInteraction\Rate\Rating;
use LaravelInteraction\Rate\Tests\Models\Channel;
use LaravelInteraction\Rate\Tests\Models\User;

/**
 * @internal
 */
final class RatingTest extends TestCase
{
    private \LaravelInteraction\Rate\Tests\Models\User $user;

    private \LaravelInteraction\Rate\Tests\Models\Channel $channel;

    private \LaravelInteraction\Rate\Rating $rating;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::query()->create();
        $this->channel = Channel::query()->create();
        $this->user->rate($this->channel);
        $this->rating = Rating::query()->firstOrFail();
    }

    public function testRatingTimestamp(): void
    {
        $this->assertInstanceOf(Carbon::class, $this->rating->created_at);
        $this->assertInstanceOf(Carbon::class, $this->rating->updated_at);
    }

    public function testScopeWithType(): void
    {
        $this->assertSame(1, Rating::query()->withType(Channel::class)->count());
        $this->assertSame(0, Rating::query()->withType(User::class)->count());
    }

    public function testGetTable(): void
    {
        $this->assertSame(config('rate.table_names.pivot'), $this->rating->getTable());
    }

    public function testRater(): void
    {
        $this->assertInstanceOf(User::class, $this->rating->rater);
    }

    public function testRatable(): void
    {
        $this->assertInstanceOf(Channel::class, $this->rating->ratable);
    }

    public function testUser(): void
    {
        $this->assertInstanceOf(User::class, $this->rating->user);
    }

    public function testIsRatedTo(): void
    {
        $this->assertTrue($this->rating->isRatedTo($this->channel));
        $this->assertFalse($this->rating->isRatedTo($this->user));
    }

    public function testIsRatedBy(): void
    {
        $this->assertFalse($this->rating->isRatedBy($this->channel));
        $this->assertTrue($this->rating->isRatedBy($this->user));
    }
}
