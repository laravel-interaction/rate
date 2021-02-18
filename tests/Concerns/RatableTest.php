<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Tests\Concerns;

use LaravelInteraction\Rate\Tests\Models\Channel;
use LaravelInteraction\Rate\Tests\Models\User;
use LaravelInteraction\Rate\Tests\TestCase;

class RatableTest extends TestCase
{
    public function modelClasses(): array
    {
        return[
            [Channel::class],
            [User::class],
        ];
    }

    /**
     * @dataProvider modelClasses
     *
     * @param \LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel|string $modelClass
     */
    public function testRatings(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        self::assertSame(1, $model->ratableRatings()->count());
        self::assertSame(1, $model->ratableRatings->count());
    }

    /**
     * @dataProvider modelClasses
     *
     * @param \LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel|string $modelClass
     */
    public function testRatersCount(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        self::assertSame(1, $model->ratersCount());
        $user->unrate($model);
        self::assertSame(1, $model->ratersCount());
        $model->loadCount('raters');
        self::assertSame(0, $model->ratersCount());
    }

    /**
     * @dataProvider modelClasses
     *
     * @param \LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel|string $modelClass
     */
    public function testRatersCountForHumans(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        self::assertSame('1', $model->ratersCountForHumans());
    }

    /**
     * @dataProvider modelClasses
     *
     * @param \LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel|string $modelClass
     */
    public function testIsRatedBy(string $modelClass): void
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
     * @dataProvider modelClasses
     *
     * @param \LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel|string $modelClass
     */
    public function testIsNotRatedBy(string $modelClass): void
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
     * @dataProvider modelClasses
     *
     * @param \LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel|string $modelClass
     */
    public function testRaters(string $modelClass): void
    {
        $user = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        self::assertSame(1, $model->raters()->count());
        $user->unrate($model);
        self::assertSame(0, $model->raters()->count());
    }

    /**
     * @dataProvider modelClasses
     *
     * @param \LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel|string $modelClass
     */
    public function testScopeWhereRatedBy(string $modelClass): void
    {
        $user = User::query()->create();
        $other = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        self::assertSame(1, $modelClass::query()->whereRatedBy($user)->count());
        self::assertSame(0, $modelClass::query()->whereRatedBy($other)->count());
    }

    /**
     * @dataProvider modelClasses
     *
     * @param \LaravelInteraction\Rate\Tests\Models\User|\LaravelInteraction\Rate\Tests\Models\Channel|string $modelClass
     */
    public function testScopeWhereNotRatedBy($modelClass): void
    {
        $user = User::query()->create();
        $other = User::query()->create();
        $model = $modelClass::query()->create();
        $user->rate($model);
        self::assertSame($modelClass::query()->whereKeyNot($model->getKey())->count(), $modelClass::query()->whereNotRatedBy($user)->count());
        self::assertSame($modelClass::query()->count(), $modelClass::query()->whereNotRatedBy($other)->count());
    }
}
