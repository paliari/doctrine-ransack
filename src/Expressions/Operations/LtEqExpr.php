<?php

namespace Paliari\Doctrine\Expressions\Operations;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FilterVO;

class LtEqExpr extends AbstractExpr
{
    public const NAME = 'lteq';

    public function create(QueryBuilder $qb, FilterVO $vo): Comparison
    {
        $key = $this->fieldKey($vo->field, static::NAME);
        $qb->setParameter($key, $vo->value, $vo->type);

        return $qb->expr()->lte($vo->field, ":$key");
    }
}
