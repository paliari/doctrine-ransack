<?php

namespace Paliari\Doctrine\VO;

use Paliari\Utils\VO\AbstractVO;

class FilterRelationVO extends AbstractVO
{
    /**
     * @var RelationVO[]
     */
    public array $relations = [];
    public ?string $field = null;
    public ?string $type = null;
}
