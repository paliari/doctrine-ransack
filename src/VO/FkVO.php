<?php

namespace Paliari\Doctrine\VO;

use Paliari\Utils\VO\AbstractVO;

class FkVO extends AbstractVO
{
    public string $modelName;
    public string $fieldName;
    public string $targetEntity;
    public JoinVO $join;
}
