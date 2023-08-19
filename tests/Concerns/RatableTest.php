<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Tests\Concerns;

use LaravelInteraction\Rate\Tests\Models\Channel;
use LaravelInteraction\Rate\Tests\Models\User;
use LaravelInteraction\Rate\Tests\TestCase;

/**
 * @internal
 */
final class RatableTest extends TestCase
{
    /**
     * @return \Iterator<array<class-string<\LaravelInteraction\Rate\Tests\Models\Channel|\LaravelInteraction\Rate\Tests\Models\User>>>
     */
    public static function provideModelClasses(): \Iterator
    {
        yield [Channel::class];

        yield [User::class];
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testRatings(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $this->assertSame(1, $model->ratableRatings()->count());
        $this->assertSame(1, $model->ratableRatings->count());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testRatersCount(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $this->assertSame(1, $model->ratersCount());
        $user->unrate($model);
        $this->assertSame(1, $model->ratersCount());
        $model->loadCount('raters');
        $this->assertSame(0, $model->ratersCount());
        $user->rate($model);
        $this->assertSame(1, $model->raters()->count());
        $this->assertSame(1, $model->raters->count());
        $paginate = $model->raters()
            ->paginate();
        $this->assertSame(1, $paginate->total());
        $this->assertCount(1, $paginate->items());
        $model->loadRatersCount(static fn ($query) => $query->whereKeyNot($user->getKey()));
        $this->assertSame(0, $model->ratersCount());
        $user2 = User::query()->create();
        $user2->rate($model);

        $model->loadRatersCount();
        $this->assertSame(2, $model->ratersCount());
        $this->assertSame(2, $model->raters()->count());
        $model->load('raters');
        $this->assertSame(2, $model->raters->count());
        $paginate = $model->raters()
            ->paginate();
        $this->assertSame(2, $paginate->total());
        $this->assertCount(2, $paginate->items());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testWithRatersCount(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $this->assertSame(0, $model->ratersCount());
        $user->rate($model);
        $model = $modelClass::query()->withRatersCount()->whereKey($model->getKey())->firstOrFail();
        $this->assertSame(1, $model->ratersCount());
        $user->rate($model);
        $model = $modelClass::query()->withRatersCount()->whereKey($model->getKey())->firstOrFail();
        $this->assertSame(1, $model->ratersCount());
        $model = $modelClass::query()->withRatersCount(
            static fn ($query) => $query->whereKeyNot($user->getKey())
        )->whereKey($model->getKey())
            ->firstOrFail();

        $this->assertSame(0, $model->ratersCount());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testRatersCountForHumans(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $this->assertSame('1', $model->ratersCountForHumans());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testIsRatedBy(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $this->assertFalse($model->isRatedBy($model));
        $user->rate($model);
        $this->assertTrue($model->isRatedBy($user));
        $model->load('raters');
        $user->unrate($model);
        $this->assertTrue($model->isRatedBy($user));
        $model->load('raters');
        $this->assertFalse($model->isRatedBy($user));
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testIsNotRatedBy(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $this->assertTrue($model->isNotRatedBy($model));
        $user->rate($model);
        $this->assertFalse($model->isNotRatedBy($user));
        $model->load('raters');
        $user->unrate($model);
        $this->assertFalse($model->isNotRatedBy($user));
        $model->load('raters');
        $this->assertTrue($model->isNotRatedBy($user));
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testRaters(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $this->assertSame(1, $model->raters()->count());
        $user->unrate($model);
        $this->assertSame(0, $model->raters()->count());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testScopeWhereRatedBy(string $modelClass): void
    {
        $user = User::query()->create();
        $other = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $this->assertSame(1, $modelClass::query()->whereRatedBy($user)->count());
        $this->assertSame(0, $modelClass::query()->whereRatedBy($other)->count());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testScopeWhereNotRatedBy(string $modelClass): void
    {
        $user = User::query()->create();
        $other = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $this->assertSame(
            $modelClass::query()->whereKeyNot($model->getKey())->count(),
            $modelClass::query()->whereNotRatedBy($user)->count()
        );
        $this->assertSame($modelClass::query()->count(), $modelClass::query()->whereNotRatedBy($other)->count());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testRatableRatingsCount(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $user->rate($model);
        $this->assertSame(2, $model->ratableRatingsCount());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testRatableRatingsCountForHumans(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $user->rate($model);
        $this->assertSame('2', $model->ratableRatingsCountForHumans());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testAvgRating(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $this->assertSame(1.0, $model->avgRating());
        $user->rate($model, 2);
        $this->assertSame(1.0, $model->avgRating());
        $model->offsetUnset('ratable_ratings_avg_rating');
        $this->assertSame(1.5, $model->avgRating());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testSumRating(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $user->rate($model);
        $this->assertSame(2.0, $model->sumRating());
        $user->rate($model);
        $this->assertSame(2.0, $model->sumRating());
        $model->offsetUnset('ratable_ratings_sum_rating');
        $this->assertSame(3.0, $model->sumRating());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testSumRatingForHumans(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $user->rate($model);
        $this->assertSame('2', $model->sumRatingForHumans());
        $user->rate($model);
        $this->assertSame('2', $model->sumRatingForHumans());
        $model->offsetUnset('ratable_ratings_sum_rating');
        $this->assertSame('3', $model->sumRatingForHumans());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testRatingPercent(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $user->rate($model);
        $this->assertSame(20.0, $model->ratingPercent());
        $this->assertSame(10.0, $model->ratingPercent(10));
    }
}
