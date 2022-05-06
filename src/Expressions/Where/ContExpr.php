<?php

namespace Paliari\Doctrine\Expressions\Where;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FilterVO;

class ContExpr extends AbstractExpr
{
    public const NAME = 'cont';

    public function create(QueryBuilder $qb, FilterVO $vo): Comparison
    {
        $key = $this->fieldKey($vo->field, static::NAME);
        $qb->setParameter($key, $this->prepareValue($vo->value), $vo->type);

        return $qb->expr()->like($vo->field, ":$key");
    }

    protected function prepareValue(string $value): string
    {
        return '%' . str_replace(' ', '%', $value) . '%';
    }
}
