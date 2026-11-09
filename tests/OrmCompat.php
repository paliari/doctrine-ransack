<?php

namespace Tests;

use Composer\InstalledVersions;
use Doctrine\ORM\Query\Expr\Join;

class OrmCompat
{
    public static function ormVersionAtLeast(string $version): bool
    {
        return version_compare(InstalledVersions::getVersion('doctrine/orm'), $version, '>=');
    }

    /**
     * Arbitrary joins: doctrine/orm 3.6+ deprecates WITH in favor of ON, which older versions do not parse.
     */
    public static function arbitraryJoinConditionType(): string
    {
        return static::ormVersionAtLeast('3.6') ? Join::ON : Join::WITH;
    }

    /**
     * doctrine/orm 3.7+ renders order directions from \SortDirection, always uppercase.
     */
    public static function dqlOrderDirection(string $direction): string
    {
        return static::ormVersionAtLeast('3.7') ? strtoupper($direction) : $direction;
    }
}
