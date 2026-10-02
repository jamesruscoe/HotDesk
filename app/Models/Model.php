<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Base for every domain model. Input is whitelisted by Form Requests and
 * passed through services, so only the primary key is guarded here.
 */
abstract class Model extends EloquentModel
{
    protected $guarded = ['id'];

    public static function getTableName(): string
    {
        return (new static())->getTable();
    }
}
