<?php
namespace Paliari\Doctrine;

use Doctrine\ORM\QueryBuilder;

abstract class AbstractRansackModel
{

    /**
     * @param array $params
     *
     * @return QueryBuilder
     */
    public static function ransack($params = [])
    {
        return Ransack::instance()->query(get_called_class(), $params);
    }

}
