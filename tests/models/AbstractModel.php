<?php
use Paliari\Doctrine\Ransack;

class AbstractModel extends \Paliari\Doctrine\AbstractRansackModel
{

    public static function find($id)
    {
        return Ransack::getEm()->find(get_called_class(), $id);
    }

}
