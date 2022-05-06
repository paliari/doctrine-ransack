<?php

namespace Paliari\Doctrine\Factories;

use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\Expressions\Where\BetweenExpr;
use Paliari\Doctrine\Expressions\Where\ContExpr;
use Paliari\Doctrine\Expressions\Where\EndExpr;
use Paliari\Doctrine\Expressions\Where\EqExpr;
use Paliari\Doctrine\Expressions\Where\ExprInterface;
use Paliari\Doctrine\Expressions\Where\GroupByExpr;
use Paliari\Doctrine\Expressions\Where\GtEqExpr;
use Paliari\Doctrine\Expressions\Where\GtExpr;
use Paliari\Doctrine\Expressions\Where\InExpr;
use Paliari\Doctrine\Expressions\Where\LtEqExpr;
use Paliari\Doctrine\Expressions\Where\LtExpr;
use Paliari\Doctrine\Expressions\Where\MatchesExpr;
use Paliari\Doctrine\Expressions\Where\NotContExpr;
use Paliari\Doctrine\Expressions\Where\NotEndExpr;
use Paliari\Doctrine\Expressions\Where\NotEqExpr;
use Paliari\Doctrine\Expressions\Where\NotInExpr;
use Paliari\Doctrine\Expressions\Where\NotMatchesExpr;
use Paliari\Doctrine\Expressions\Where\NotNullExpr;
use Paliari\Doctrine\Expressions\Where\NotStartExpr;
use Paliari\Doctrine\Expressions\Where\NullExpr;
use Paliari\Doctrine\Expressions\Where\OrderByExpr;
use Paliari\Doctrine\Expressions\Where\StartExpr;

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
