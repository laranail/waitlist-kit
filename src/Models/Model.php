<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Models;

use Simtabi\Laranail\WaitlistKit\Support\Tables;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * The kit's models: ULID keys, and the table and connection the configuration names, so the kit never collides
 * with an application's own tables.
 */
abstract class Model extends Eloquent
{
    use HasUlids;

    public function getTable(): string
    {
        return Tables::name($this->baseTable());
    }

    public function getConnectionName(): ?string
    {
        return Tables::connection() ?? parent::getConnectionName();
    }

    /** The table name without the configured prefix. */
    abstract protected function baseTable(): string;
}
