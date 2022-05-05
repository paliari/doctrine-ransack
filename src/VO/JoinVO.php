<?php

namespace Paliari\Doctrine\VO;

use Paliari\Utils\VO\AbstractVO;

class JoinVO extends AbstractVO
{
    public string $join;
    public string $alias;
    public ?string $conditionType = null;
    public ?string $condition = null;
    public ?string $indexBy = null;
}
