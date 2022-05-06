<?php

namespace Paliari\Doctrine\Factories;

use Paliari\Doctrine\QueryBuilderManger;
use Paliari\Doctrine\RansackBuilder;
use Paliari\Doctrine\RansackConfig;

class RansackBuilderFactory
{
    public function __construct(protected RansackConfig $config)
    {
    }

    public function create(QueryBuilderManger $qbManager, string $modelName, string $alias = 't'): RansackBuilder
    {
        return new RansackBuilder($this->config, $qbManager, $modelName, $alias);
    }
}
