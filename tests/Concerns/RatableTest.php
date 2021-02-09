<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Tests\Concerns;

use LaravelInteraction\Rate\Tests\Models\Channel;
use LaravelInteraction\Rate\Tests\Models\User;
use LaravelInteraction\Rate\Tests\TestCase;
use Mockery;

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

    public function data(): array
    {
        return [
            [0, '0', '0', '0'],
            [1, '1', '1', '1'],
            [12, '12', '12', '12'],
            [123, '123', '123', '123'],
            [12345, '12.3K', '12.35K', '12.34K'],
            [1234567, '1.2M', '1.23M', '1.23M'],
            [123456789, '123.5M', '123.46M', '123.46M'],
            [12345678901, '12.3B', '12.35B', '12.35B'],
            [1234567890123, '1.2T', '1.23T', '1.23T'],
            [1234567890123456, '1.2Qa', '1.23Qa', '1.23Qa'],
            [1234567890123456789, '1.2Qi', '1.23Qi', '1.23Qi'],
        ];
    }

    /**
     * @dataProvider data
     *
     * @param mixed $actual
     * @param mixed $onePrecision
     * @param mixed $twoPrecision
     * @param mixed $halfDown
     */
    public function testRatersCountForHumans($actual, $onePrecision, $twoPrecision, $halfDown): void
    {
        $channel = Mockery::mock(Channel::class);
        $channel->shouldReceive('ratersCountForHumans')->passthru();
        $channel->shouldReceive('ratersCount')->andReturn($actual);
        self::assertSame($onePrecision, $channel->ratersCountForHumans());
        self::assertSame($twoPrecision, $channel->ratersCountForHumans(2));
        self::assertSame($halfDown, $channel->ratersCountForHumans(2, PHP_ROUND_HALF_DOWN));
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
