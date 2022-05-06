<?php

namespace Paliari\Doctrine\VO;

use Paliari\Utils\VO\AbstractVO;

class FilterVO extends AbstractVO
{
    public string $field = '';
    public mixed $value = null;
    public ?string $type = null;
}
