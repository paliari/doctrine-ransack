<?php
namespace Paliari\Doctrine;

/**
 * Class TraitRansackModel
 * @package Paliari\Doctrine
 */
trait TraitRansackModel
{

    /**
     * @param array $params
     *
     * @return RansackQueryBuilder
     */
    public static function ransack($params = [])
    {
        return Ransack::instance()->query(static::query(), get_called_class(), $params);
    }

    /**
     * Override this method if you need a custom query builder.
     *
     * @param string $alias
     *
     * @return RansackQueryBuilder
     */
    public static function query($alias = 't')
    {
        return RansackQueryBuilder::create(static::getEm(), get_called_class(), $alias)->select($alias);
    }

    /**
     * Override this method to return your Entity Manager.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public static function getEm()
    {
        throw new \DomainException('EntityManager not defined! Override the method AbstractRansackModel::getEm().');
    }

}
