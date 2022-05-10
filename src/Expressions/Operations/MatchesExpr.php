<?php

namespace Paliari\Doctrine\Expressions\Operations;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FilterVO;

class MatchesExpr extends AbstractExpr
{
    public const NAME = 'matches';

    public function create(QueryBuilder $qb, FilterVO $vo): Comparison
    {
        $key = $this->fieldKey($vo->field, static::NAME);
        $qb->setParameter($key, $vo->value, $vo->type);

        return $qb->expr()->like($vo->field, ":$key");
    }
}
