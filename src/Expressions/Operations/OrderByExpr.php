<?php

namespace Paliari\Doctrine\Expressions\Operations;

use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\VO\FilterVO;
use ReflectionMethod;

class OrderByExpr extends AbstractExpr
{
    public const NAME = 'order_by';
    public const DIRECTIONS = ['ASC', 'DESC'];

    protected static ?bool $sortDirectionSupported = null;

    /**
     * @throws RansackException
     */
    public function create(QueryBuilder $qb, FilterVO $vo): mixed
    {
        if (!is_string($vo->value) || !in_array(strtoupper($vo->value), static::DIRECTIONS, true)) {
            $value = is_scalar($vo->value) ? $vo->value : get_debug_type($vo->value);
            throw new RansackException("Order direction '$value' is invalid!");
        }
        $qb->addOrderBy($vo->field, $this->direction($vo->value));

        return null;
    }

    /**
     * doctrine/orm 3.7+ deprecates string directions in favor of \SortDirection; older versions only accept strings.
     */
    protected function direction(string $value): string|\SortDirection
    {
        static::$sortDirectionSupported ??= str_contains(
            (string)(new ReflectionMethod(QueryBuilder::class, 'addOrderBy'))->getParameters()[1]->getType(),
            'SortDirection',
        );
        if (!static::$sortDirectionSupported) {
            return $value;
        }

        return 'ASC' === strtoupper($value) ? \SortDirection::Ascending : \SortDirection::Descending;
    }
}
