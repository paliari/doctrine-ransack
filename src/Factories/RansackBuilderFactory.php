<?php

namespace Paliari\Doctrine\Factories;

use Paliari\Doctrine\Helpers\QueryBuilderHelper;
use Paliari\Doctrine\RansackBuilder;
use Paliari\Doctrine\RansackConfig;

class RansackBuilderFactory
{
    public function __construct(protected RansackConfig $config)
    {
    }

    public function create(QueryBuilderHelper $qbHelper, string $entityName, string $alias = 't'): RansackBuilder
    {
        return new RansackBuilder($this->config, $qbHelper, $entityName, $alias);
    }
}
