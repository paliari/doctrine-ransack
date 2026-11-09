<?php

namespace Paliari\Doctrine\Expressions\Operations;

use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\VO\FilterVO;

class OrderByExpr extends AbstractExpr
{
    public const NAME = 'order_by';
    public const DIRECTIONS = ['ASC', 'DESC'];

    /**
     * @throws RansackException
     */
    public function create(QueryBuilder $qb, FilterVO $vo): mixed
    {
        if (!is_string($vo->value) || !in_array(strtoupper($vo->value), static::DIRECTIONS, true)) {
            $value = is_scalar($vo->value) ? $vo->value : get_debug_type($vo->value);
            throw new RansackException("Order direction '$value' is invalid!");
        }
        $qb->addOrderBy($vo->field, $vo->value);

        return null;
    }
}
