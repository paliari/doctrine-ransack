<?php

namespace Paliari\Doctrine\Factories;

use JetBrains\PhpStorm\Pure;
use Paliari\Doctrine\QueryBuilderManger;
use Paliari\Doctrine\RansackConfig;
use Paliari\Doctrine\RansackFilter;

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
