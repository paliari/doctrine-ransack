<?php

namespace Paliari\Doctrine;

abstract class AbstractRansackRepository implements RansackRepositoryInterface
{

    /**
     * @param array $params
     *
     * @return RansackQueryBuilder
     */
    public static function ransack(array $params = [])
    {
        return Ransack::instance()->query(static::query(), static::modelName(), $params);
    }

    /**
     * @param string $alias
     *
     * @return RansackQueryBuilder
     */
    public static function query($alias = 't')
    {
        return RansackQueryBuilder::create(static::getEm(), static::modelName(), $alias)->select($alias);
    }

    abstract protected static function modelName(): string;

}
