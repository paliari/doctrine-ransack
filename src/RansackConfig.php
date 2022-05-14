<?php

namespace Paliari\Doctrine;

use Doctrine\ORM\EntityManager;
use JetBrains\PhpStorm\Pure;
use Paliari\Doctrine\Expressions\ExprRelationManager;
use Paliari\Doctrine\Expressions\ParamExprParser;
use Paliari\Doctrine\Factories\ExprFactory;
use Paliari\Doctrine\Helpers\EntityHelper;

class RansackConfig
{
    protected ExprFactory $exprFactory;
    protected ParamExprParser $paramExprParser;
    protected ExprRelationManager $exprRelationManager;
    protected EntityHelper $entityHelper;

    #[Pure]
    public function __construct(protected EntityManager $em, ?CustomAssociationInterface $customAssociation = null)
    {
        $this->entityHelper = new EntityHelper($this->em, $customAssociation);
        $this->exprRelationManager = new ExprRelationManager($this->entityHelper);
        $this->paramExprParser = new ParamExprParser();
        $this->exprFactory = new ExprFactory();
    }

    public function getParamExprParser(): ParamExprParser
    {
        return $this->paramExprParser;
    }

    public function getExprFactory(): ExprFactory
    {
        return $this->exprFactory;
    }

    public function getExprRelationManager(): ExprRelationManager
    {
        return $this->exprRelationManager;
    }

    public function getEntityHelper(): EntityHelper
    {
        return $this->entityHelper;
    }
}
