<?php

namespace Paliari\Doctrine\Expressions\Where;

use Paliari\Doctrine\Exceptions\RansackException;

class ExprFactory
{
    public const MAP = [
        NotEqExpr::NAME => NotEqExpr::class,
        NotInExpr::NAME => NotInExpr::class,
        NotNullExpr::NAME => NotNullExpr::class,
        EqExpr::NAME => EqExpr::class,
        LtExpr::NAME => LtExpr::class,
        LtEqExpr::NAME => LtEqExpr::class,
        GtExpr::NAME => GtExpr::class,
        GtEqExpr::NAME => GtEqExpr::class,
        InExpr::NAME => InExpr::class,
        NullExpr::NAME => NullExpr::class,
        MatchesExpr::NAME => MatchesExpr::class,
        ContExpr::NAME => ContExpr::class,
        StartExpr::NAME => StartExpr::class,
        EndExpr::NAME => EndExpr::class,
        BetweenExpr::NAME => BetweenExpr::class,
        NotContExpr::NAME => NotContExpr::class,
        NotEndExpr::NAME => NotEndExpr::class,
        NotStartExpr::NAME => NotStartExpr::class,
        NotMatchesExpr::NAME => NotMatchesExpr::class,
        OrderByExpr::NAME => OrderByExpr::class,
        GroupByExpr::NAME => GroupByExpr::class,
    ];

    protected array $instances = [];

    /**
     * @throws RansackException
     */
    public function get(string $name): ExprInterface
    {
        if (!isset(static::MAP[$name])) {
            throw new RansackException("Ransack expression '$name' not found!");
        }
        $className = static::MAP[$name];
        if (!isset($this->instances[$name])) {
            $this->instances[$name] = new $className;
        }

        return $this->instances[$name];
    }
}
