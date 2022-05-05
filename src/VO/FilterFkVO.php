<?php

namespace Paliari\Doctrine\VO;

use Paliari\Utils\VO\AbstractVO;

class FilterFkVO extends AbstractVO
{
    /**
     * @var FkVO[]
     */
    public array $fks = [];
    public ?string $field = null;
    public ?string $type = null;

    protected function set($key, $value)
    {
        if ($key === 'fks') {
            $value = array_map(fn($f) => new FkVO($f), $value);
        }
        parent::set($key, $value);
    }
}
