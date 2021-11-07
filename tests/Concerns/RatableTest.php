<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Tests\Concerns;

use Iterator;
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
    public function provideModelClasses(): Iterator
    {
        yield [Channel::class];
        yield [User::class];
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testRatings($modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        self::assertSame(1, $model->ratableRatings()->count());
        self::assertSame(1, $model->ratableRatings->count());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testRatersCount($modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        self::assertSame(1, $model->ratersCount());
        $user->unrate($model);
        self::assertSame(1, $model->ratersCount());
        $model->loadCount('raters');
        self::assertSame(0, $model->ratersCount());
        $user->rate($model);
        self::assertSame(1, $model->raters()->count());
        self::assertSame(1, $model->raters->count());
        $paginate = $model->raters()
            ->paginate();
        self::assertSame(1, $paginate->total());
        self::assertCount(1, $paginate->items());
        $model->loadRatersCount(function ($query) use ($user) {
            return $query->whereKeyNot($user->getKey());
        });
        self::assertSame(0, $model->ratersCount());
        $user2 = User::query()->create();
        $user2->rate($model);

        $model->loadRatersCount();
        self::assertSame(2, $model->ratersCount());
        self::assertSame(2, $model->raters()->count());
        $model->load('raters');
        self::assertSame(2, $model->raters->count());
        $paginate = $model->raters()
            ->paginate();
        self::assertSame(2, $paginate->total());
        self::assertCount(2, $paginate->items());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testWithRatersCount($modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        self::assertSame(0, $model->ratersCount());
        $user->rate($model);
        $model = $modelClass::query()->withRatersCount()->whereKey($model->getKey())->firstOrFail();
        self::assertSame(1, $model->ratersCount());
        $user->rate($model);
        $model = $modelClass::query()->withRatersCount()->whereKey($model->getKey())->firstOrFail();
        self::assertSame(1, $model->ratersCount());
        $model = $modelClass::query()->withRatersCount(
            function ($query) use ($user) {
                return $query->whereKeyNot($user->getKey());
            }
        )->whereKey($model->getKey())
            ->firstOrFail();

        self::assertSame(0, $model->ratersCount());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testRatersCountForHumans($modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        self::assertSame('1', $model->ratersCountForHumans());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testIsRatedBy($modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        self::assertFalse($model->isRatedBy($model));
        $user->rate($model);
        self::assertTrue($model->isRatedBy($user));
        $model->load('raters');
        $user->unrate($model);
        self::assertTrue($model->isRatedBy($user));
        $model->load('raters');
        self::assertFalse($model->isRatedBy($user));
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testIsNotRatedBy($modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        self::assertTrue($model->isNotRatedBy($model));
        $user->rate($model);
        self::assertFalse($model->isNotRatedBy($user));
        $model->load('raters');
        $user->unrate($model);
        self::assertFalse($model->isNotRatedBy($user));
        $model->load('raters');
        self::assertTrue($model->isNotRatedBy($user));
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testRaters($modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        self::assertSame(1, $model->raters()->count());
        $user->unrate($model);
        self::assertSame(0, $model->raters()->count());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testScopeWhereRatedBy($modelClass): void
    {
        $user = User::query()->create();
        $other = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        self::assertSame(1, $modelClass::query()->whereRatedBy($user)->count());
        self::assertSame(0, $modelClass::query()->whereRatedBy($other)->count());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testScopeWhereNotRatedBy($modelClass): void
    {
        $user = User::query()->create();
        $other = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        self::assertSame(
            $modelClass::query()->whereKeyNot($model->getKey())->count(),
            $modelClass::query()->whereNotRatedBy($user)->count()
        );
        self::assertSame($modelClass::query()->count(), $modelClass::query()->whereNotRatedBy($other)->count());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testRatableRatingsCount($modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $user->rate($model);
        self::assertSame(2, $model->ratableRatingsCount());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testRatableRatingsCountForHumans($modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $user->rate($model);
        self::assertSame('2', $model->ratableRatingsCountForHumans());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testAvgRating($modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        self::assertSame(1.0, $model->avgRating());
        $user->rate($model, 2);
        self::assertSame(1.0, $model->avgRating());
        $model->offsetUnset('ratable_ratings_avg_rating');
        self::assertSame(1.5, $model->avgRating());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testSumRating($modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $user->rate($model);
        self::assertSame(2.0, $model->sumRating());
        $user->rate($model);
        self::assertSame(2.0, $model->sumRating());
        $model->offsetUnset('ratable_ratings_sum_rating');
        self::assertSame(3.0, $model->sumRating());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testSumRatingForHumans($modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $user->rate($model);
        self::assertSame('2', $model->sumRatingForHumans());
        $user->rate($model);
        self::assertSame('2', $model->sumRatingForHumans());
        $model->offsetUnset('ratable_ratings_sum_rating');
        self::assertSame('3', $model->sumRatingForHumans());
    }

    /**
     * @dataProvider provideModelClasses
     *
     * @param class-string<\LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel> $modelClass
     */
    public function testRatingPercent($modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        $user->rate($model);
        self::assertSame(20.0, $model->ratingPercent());
        self::assertSame(10.0, $model->ratingPercent(10));
    }
}
