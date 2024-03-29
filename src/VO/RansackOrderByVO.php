<?php

namespace Paliari\Doctrine\VO;

use Paliari\Utils\VO\AbstractVO;

class RansackOrderByVO extends AbstractVO
{
    public const ASC = 'ASC';
    public const DESC = 'DESC';

    public string $field = '';
    public string $order = self::ASC;
}
