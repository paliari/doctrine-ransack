<?php

namespace Paliari\Doctrine\VO;

use Paliari\Utils\VO\AbstractVO;

class FilterFkVO extends AbstractVO
{
    /**
     * @var RelationVO[]
     */
    public array $fks = [];
    public ?string $field = null;
    public ?string $type = null;
}
