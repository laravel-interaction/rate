<?php

declare(strict_types=1);

namespace LaravelInteraction\Rate\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelInteraction\Rate\Concerns\Ratable;

/**
 * @method static \LaravelInteraction\Rate\Tests\Models\Channel|\Illuminate\Database\Eloquent\Builder query()
 */
class Channel extends Model
{
    use Ratable;
}
