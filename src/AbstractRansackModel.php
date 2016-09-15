<?php
namespace Paliari\Doctrine;

abstract class AbstractRansackModel
{

    /**
     * @param array $params
     *
     * @return RansackQueryBuilder
     */
    public static function ransack($params = [])
    {
        return Ransack::instance()->query(get_called_class(), $params);
    }

}
