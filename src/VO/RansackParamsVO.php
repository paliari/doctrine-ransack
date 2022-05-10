<?php

namespace Paliari\Doctrine\VO;

use Paliari\Utils\VO\AbstractVO;

class RansackParamsVO extends AbstractVO
{
    /**
     * @var array[]
     */
    public array $where = [];

    /**
     * @var RansackOrderByVO[]
     */
    public array $orderBy = [];

    /**
     * @var string[]
     */
    public array $groupBy = [];
}
