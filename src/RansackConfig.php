<?php

namespace Paliari\Doctrine;

use Paliari\Doctrine\Expressions\FilterFkManager;
use Paliari\Doctrine\Expressions\ParamFilterParser;
use Paliari\Doctrine\Expressions\Where\ExprFactory;

class RansackConfig
{
    protected ExprFactory $exprFactory;
    protected ParamFilterParser $paramFilterParser;
    protected FilterFkManager $filterFkManager;

    public function __construct(?CustomAssociationInterface $customAssociation = null)
    {
        $this->exprFactory = new ExprFactory();
        $this->paramFilterParser = new ParamFilterParser();
        $this->filterFkManager = new FilterFkManager();
        $this->setCustomAssociation($customAssociation);
    }

    public function setCustomAssociation(?CustomAssociationInterface $customAssociation): static
    {
        $this->filterFkManager->setCustomAssociation($customAssociation);

        return $this;
    }

    public function getParamFilterParser(): ParamFilterParser
    {
        return $this->paramFilterParser;
    }

    public function getExprFactory(): ExprFactory
    {
        return $this->exprFactory;
    }

    public function getFilterFkManager(): FilterFkManager
    {
        return $this->filterFkManager;
    }
}
