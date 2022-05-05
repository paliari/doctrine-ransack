<?php

namespace Paliari\Doctrine\VO;

use Paliari\Utils\VO\AbstractVO;

class ParamFilterVO extends AbstractVO
{
    public string $key = '';
    public string $exprName = '';
    public mixed $value = null;
}
