<?php

namespace Paliari\Doctrine\Factories;

use JetBrains\PhpStorm\Pure;
use Paliari\Doctrine\Helpers\QueryBuilderHelper;
use Paliari\Doctrine\RansackConfig;
use Paliari\Doctrine\Helpers\RansackFilterHelper;

class RansackFilterHelperFactory
{
    public function __construct(protected RansackConfig $config)
    {
    }

    #[Pure]
    public function create(QueryBuilderHelper $qbHelper, string $entityName, string $alias = 't'): RansackFilterHelper
    {
        return new RansackFilterHelper($this->config, $qbHelper, $entityName, $alias);
    }
}
