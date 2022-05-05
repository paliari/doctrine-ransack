<?php

namespace Paliari\Doctrine;

use JetBrains\PhpStorm\Pure;

class RansackFilterFactory
{
    public function __construct(protected RansackConfig $config)
    {
    }

    #[Pure]
    public function create(QueryBuilderManger $qbManager, string $modelName, string $alias = 't'): RansackFilter
    {
        return new RansackFilter($this->config, $qbManager, $modelName, $alias);
    }
}
