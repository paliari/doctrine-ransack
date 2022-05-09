<?php

namespace Paliari\Doctrine\VO;

use Paliari\Utils\VO\AbstractVO;

class RelationVO extends AbstractVO
{
    public string $modelName;
    public string $fieldName;
    public string $targetEntity;
    public JoinVO $join;
}
