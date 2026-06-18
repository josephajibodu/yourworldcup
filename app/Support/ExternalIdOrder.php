<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ExternalIdOrder
{
    public static function apply(Builder $builder): Builder
    {
        return $builder->orderByRaw(self::expression());
    }

    public static function expression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => 'external_id::integer',
            'sqlite' => 'CAST(external_id AS INTEGER)',
            'mysql', 'mariadb' => 'CAST(external_id AS UNSIGNED)',
            default => 'external_id',
        };
    }
}
