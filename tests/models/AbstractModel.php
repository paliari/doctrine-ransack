<?php

class AbstractModel extends \Paliari\Doctrine\AbstractRansackModel
{

    public static function find($id)
    {
        return static::getEm()->find(get_called_class(), $id);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public static function getEm()
    {
        return EM::getEm();
    }

}
