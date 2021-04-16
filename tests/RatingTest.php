<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Tests;

use Illuminate\Support\Carbon;
use LaravelInteraction\Rate\Rating;
use LaravelInteraction\Rate\Tests\Models\Channel;
use LaravelInteraction\Rate\Tests\Models\User;

class RatingTest extends TestCase
{
    /**
     * @var \LaravelInteraction\Rate\Tests\Models\User
     */
    protected $user;

    /**
     * @var \LaravelInteraction\Rate\Tests\Models\Channel
     */
    protected $channel;

    /**
     * @var \LaravelInteraction\Rate\Rating
     */
    protected $rating;

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
        self::assertInstanceOf(Carbon::class, $this->rating->created_at);
        self::assertInstanceOf(Carbon::class, $this->rating->updated_at);
    }

    public function testScopeWithType(): void
    {
        self::assertSame(1, Rating::query()->withType(Channel::class)->count());
        self::assertSame(0, Rating::query()->withType(User::class)->count());
    }

    public function testGetTable(): void
    {
        self::assertSame(config('rate.table_names.ratings'), $this->rating->getTable());
    }

    public function testRater(): void
    {
        self::assertInstanceOf(User::class, $this->rating->rater);
    }

    public function testRatable(): void
    {
        self::assertInstanceOf(Channel::class, $this->rating->ratable);
    }

    public function testUser(): void
    {
        self::assertInstanceOf(User::class, $this->rating->user);
    }

    public function testIsRatedTo(): void
    {
        self::assertTrue($this->rating->isRatedTo($this->channel));
        self::assertFalse($this->rating->isRatedTo($this->user));
    }

    public function testIsRatedBy(): void
    {
        self::assertFalse($this->rating->isRatedBy($this->channel));
        self::assertTrue($this->rating->isRatedBy($this->user));
    }
}
