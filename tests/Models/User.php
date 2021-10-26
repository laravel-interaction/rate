<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelInteraction\Rate\Concerns\Ratable;
use LaravelInteraction\Rate\Concerns\Rater;

/**
 * @method static \LaravelInteraction\Rate\Tests\Models\User|\Illuminate\Database\Eloquent\Builder query()
 */
class User extends Model
{
    use Rater;
    use Ratable;
}
