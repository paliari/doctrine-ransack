<?php

namespace Tests\TestCases\Functional\RansackQueryBuilder;

use Faker\Factory;
use Faker\Generator;
use Paliari\Doctrine\Ransack;
use Paliari\Doctrine\RansackConfig;
use Paliari\Doctrine\VO\WhereParamsVO;
use PHPUnit\Framework\TestCase;
use Tests\CustomAssociation;
use Tests\EM;
use Tests\Factories\PersonFactory;
use Tests\Factories\UserFactory;
use User;

class CustomAssociationTest extends TestCase
{
    protected PersonFactory $personFactory;
    protected UserFactory $userFactory;
    protected Ransack $ransack;
    protected Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->personFactory = new PersonFactory();
        $this->userFactory = new UserFactory();
        $this->ransack = new Ransack(new RansackConfig());
        $this->ransack->getConfig()->setCustomAssociation(new CustomAssociation());
        $this->faker = Factory::create('pt_BR');
    }

    public function testFilter()
    {
        $modelName = User::class;
        $alias = 't';
        $person1 = $this->personFactory->create([]);
        $person2 = $this->personFactory->create([]);
        $user1 = $this->userFactory->create([], $person1);
        $user2 = $this->userFactory->create(['email' => $this->faker->email], $person2);
        $paramsVO = new WhereParamsVO();
        $paramsVO->where = [
            'custom_email_eq' => $person1->email,
            'id_order_by' => 'asc',
        ];
        $qb = EM::getEm()->createQueryBuilder()->from($modelName, $alias);
        $this->assertNotEquals($user2->email, $user2->getPerson()->email);
        $res = $this->ransack->query($qb, $modelName, $alias)
            ->where($paramsVO)
            ->includes()
            ->getQuery()
            ->getResult();
        $this->assertCount(1, $res);
        $this->assertEquals($user1, $res[0]);
    }
}
