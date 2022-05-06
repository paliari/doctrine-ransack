<?php

namespace Paliari\Doctrine\VO;

use Paliari\Utils\VO\AbstractVO;

class WhereParamsVO extends AbstractVO
{
    /**
     * @var array[]
     */
    public array $where = [];

    /**
     * @var WhereOrderByVO[]
     */
    public array $orderBy = [];

    /**
     * @var string[]
     */
    public array $groupBy = [];
}
