<?php

namespace Paliari\Doctrine\Expressions\Where;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FilterVO;

class GtEqExpr extends AbstractExpr
{
    public const NAME = 'gteq';

    public function create(QueryBuilder $qb, FilterVO $vo): Comparison
    {
        $key = $this->fieldKey($vo->field, static::NAME);
        $qb->setParameter($key, $vo->value, $vo->type);

        return $qb->expr()->gte($vo->field, ":$key");
    }
}
