<?php

namespace Paliari\Doctrine\Factories;

use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\Expressions\Operations\BetweenExpr;
use Paliari\Doctrine\Expressions\Operations\BlankExpr;
use Paliari\Doctrine\Expressions\Operations\ContExpr;
use Paliari\Doctrine\Expressions\Operations\EndExpr;
use Paliari\Doctrine\Expressions\Operations\EqExpr;
use Paliari\Doctrine\Expressions\Operations\ExprInterface;
use Paliari\Doctrine\Expressions\Operations\GroupByExpr;
use Paliari\Doctrine\Expressions\Operations\GtEqExpr;
use Paliari\Doctrine\Expressions\Operations\GtExpr;
use Paliari\Doctrine\Expressions\Operations\InExpr;
use Paliari\Doctrine\Expressions\Operations\LtEqExpr;
use Paliari\Doctrine\Expressions\Operations\LtExpr;
use Paliari\Doctrine\Expressions\Operations\MatchesExpr;
use Paliari\Doctrine\Expressions\Operations\NotContExpr;
use Paliari\Doctrine\Expressions\Operations\NotEndExpr;
use Paliari\Doctrine\Expressions\Operations\NotEqExpr;
use Paliari\Doctrine\Expressions\Operations\NotInExpr;
use Paliari\Doctrine\Expressions\Operations\NotMatchesExpr;
use Paliari\Doctrine\Expressions\Operations\NotNullExpr;
use Paliari\Doctrine\Expressions\Operations\NotStartExpr;
use Paliari\Doctrine\Expressions\Operations\NullExpr;
use Paliari\Doctrine\Expressions\Operations\OrderByExpr;
use Paliari\Doctrine\Expressions\Operations\PresentExpr;
use Paliari\Doctrine\Expressions\Operations\StartExpr;

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
        BlankExpr::NAME => BlankExpr::class,
        PresentExpr::NAME => PresentExpr::class,
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
