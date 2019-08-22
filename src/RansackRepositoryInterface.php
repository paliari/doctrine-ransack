<?php

namespace Paliari\Doctrine;

interface RansackRepositoryInterface
{

    /**
     * @param array $params
     *
     * @return RansackQueryBuilder
     */
    public static function ransack(array $params = []);

    /**
     * @param string $alias
     *
     * @return RansackQueryBuilder
     */
    public static function query(string $alias = 't');

    /**
     * Override this method to return your Entity Manager.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public static function getEm();

}
