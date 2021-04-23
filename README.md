# Laravel Rate

User rate/unrate behaviour for Laravel.

<p align="center">
<a href="https://packagist.org/packages/laravel-interaction/rate"><img src="https://poser.pugx.org/laravel-interaction/rate/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel-interaction/rate"><img src="https://poser.pugx.org/laravel-interaction/rate/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel-interaction/rate"><img src="https://poser.pugx.org/laravel-interaction/rate/v/unstable.svg" alt="Latest Unstable Version"></a>
<a href="https://packagist.org/packages/laravel-interaction/rate"><img src="https://poser.pugx.org/laravel-interaction/rate/license" alt="License"></a>
</p>

> **Requires [PHP 7.2.0+](https://php.net/releases/)**

Require Laravel Rate using [Composer](https://getcomposer.org):

```bash
composer require laravel-interaction/rate
```

## Usage

### Setup Rater

```php
use Illuminate\Database\Eloquent\Model;
use LaravelInteraction\Rate\Concerns\Rater;

class User extends Model
{
    use Rater;
}
```

### Setup Ratable

```php
use Illuminate\Database\Eloquent\Model;
use LaravelInteraction\Rate\Concerns\Ratable;

class Channel extends Model
{
    use Ratable;
}
```

### Rater

```php
use LaravelInteraction\Rate\Tests\Models\Channel;
/** @var \LaravelInteraction\Rate\Tests\Models\User $user */
/** @var \LaravelInteraction\Rate\Tests\Models\Channel $channel */
// Rate to Ratable
$user->rate($channel);
// rate is only allowed to be called once
$user->rateOnce($channel);
$user->unrate($channel);
$user->toggleRate($channel);

// Compare Ratable
$user->hasRated($channel);
$user->hasNotRated($channel);

// Get rated info
$user->raterRatings()->count(); 

// with type
$user->raterRatings()->withType(Channel::class)->count(); 

// get rated channels
Channel::query()->whereRatedBy($user)->get();

// get rated channels doesnt rated
Channel::query()->whereNotRatedBy($user)->get();
```

### Ratable

```php
use LaravelInteraction\Rate\Tests\Models\User;
use LaravelInteraction\Rate\Tests\Models\Channel;
/** @var \LaravelInteraction\Rate\Tests\Models\User $user */
/** @var \LaravelInteraction\Rate\Tests\Models\Channel $channel */
// Compare Rater
$channel->isRatedBy($user); 
$channel->isNotRatedBy($user);
// Get raters info
$channel->raters->each(function (User $user){
    echo $user->getKey();
});
$channel->loadRatersCount();
$channels = Channel::query()->withRatersCount()->get();
$channels->each(function (Channel $channel){
    echo $channel->raters()->count(); // 1100
    echo $channel->raters_count; // "1100"
    echo $channel->ratersCount(); // 1100
    echo $channel->ratersCountForHumans(); // "1.1K"
});
```

### Events

| Event | Fired |
| --- | --- |
| `LaravelInteraction\Rate\Events\Rated` | When an object get rated. |
| `LaravelInteraction\Rate\Events\Rerated` | When an object get rerated. |
| `LaravelInteraction\Rate\Events\Unrated` | When an object get unrated. |

## License

Laravel Rate is an open-sourced software licensed under the [MIT license](LICENSE).
