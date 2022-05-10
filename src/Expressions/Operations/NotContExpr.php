<?php

namespace Paliari\Doctrine\Expressions\Operations;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FilterVO;

class NotContExpr extends AbstractExpr
{
    public const NAME = 'not_cont';

    public function create(QueryBuilder $qb, FilterVO $vo): Comparison
    {
        $key = $this->fieldKey($vo->field, static::NAME);
        $qb->setParameter($key, $this->prepareValue($vo->value), $vo->type);

        return $qb->expr()->notLike($vo->field, ":$key");
    }

    protected function prepareValue(string $value): string
    {
        return '%' . str_replace(' ', '%', $value) . '%';
    }
}
