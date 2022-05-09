# doctrine-ransack
#### Installation
	
	$ composer require paliari/doctrine-ransack

#### Configuration

Your setup, example

```php
<?php
use Paliari\Doctrine\Ransack;
use Paliari\Doctrine\RansackConfig;

$ransack = new Ransack(new RansackConfig());

```
### Usage

```php
<?php
use Paliari\Doctrine\Ransack;
use Paliari\Doctrine\RansackConfig;
use Paliari\Doctrine\VO\WhereOrderByVO;
use Paliari\Doctrine\VO\WhereParamsVO;

$modelName = User::class;
$alias = 't';
$paramsVO = new WhereParamsVO();
$paramsVO->where = [
    'person.address.street_cont' => 'Av Brasil',
    'person.address.city_eq' => 'Maringá',
    'id_order_by' => 'asc',
];
$paramsVO->orderBy = [
    new WhereOrderByVO(['field' => 'person.name', 'order' => 'ASC']),
    new WhereOrderByVO(['field' => 'person.id', 'order' => 'DESC']),
];
$paramsVO->groupBy = [
    'person.name',
    'person.address_id',
];
$qb = $entityManager->createQueryBuilder()->from($modelName, $alias);
$rb = $this->ransack
    ->query($qb, $modelName, $alias)
    ->includes()
    ->where($paramsVO);
$users = $rb->getQuery()->getResult();

// Using includes
$includes = [
  'only' => ['id', 'email'],
  'include' => [
    'person' ['only' => ['id', 'name']],
  ],
];
$rows = $rb->includes($includes)->getArrayResult();


```
### Custom Association

Your class of get custom association

```php
<?php

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\CustomAssociationInterface;
use Paliari\Doctrine\VO\RelationVO;
use Paliari\Doctrine\VO\JoinVO;
use Person;
use User;

class CustomAssociation implements CustomAssociationInterface
{
    public function __invoke(QueryBuilder $qb, string $modelName, string $alias, string $field): ?RelationVO
    {
        if (User::class === $modelName && 'custom' == $field) {
            $relationVO = new RelationVO();
            $relationVO->modelName = $modelName;
            $relationVO->fieldName = $field;
            $relationVO->targetEntity = Person::class;
            $joinVO = new JoinVO();
            $joinVO->join = Person::class;
            $joinVO->alias = "{$alias}_$field";
            $joinVO->conditionType = Join::WITH;
            $joinVO->condition = "$alias.email = $joinVO->alias.email";
            $relationVO->join = $joinVO;

            return $relationVO;
        }

        return null;
    }
}
```
Setup with CustomAssociation
```php
<?php
use Paliari\Doctrine\Ransack;
use Paliari\Doctrine\RansackConfig;

$customAssociation = new CustomAssociation();
$config = new RansackConfig();
$config->setCustomAssociation($customAssociation);
$ransack = new Ransack($config);

```

## Filters

  - #### eq (equals)
    - Example: 
  
      ```json
      {"field_eq": "Fulano da Silva"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field = 'Fulano da Silva'
      ```
    
  - #### not_eq (not equals)
    - Example: 
  
      ```json
      {"field_not_eq": "Fulano da Silva"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field <> 'Fulano da Silva'
      ```
    
  - #### in (in)
    - Example: 
  
      ```json
      {"field_in": [13, 21, 124, 525]}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field IN (13, 21, 124, 525)
      ```
    
  - #### not_in (not in)
    - Example: 
  
      ```json
      {"field_not_in": [13, 21, 124, 525]}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field NOT IN (13, 21, 124, 525)
      ```
    
  - #### null (null)
    - Example: 
  
      ```json
      {"field_null": null}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field IS NULL
      ```
    
  - #### not_null (not null)
    - Example: 
  
      ```json
      {"field_not_null": null}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field IS NOT NULL
      ```
    
  - #### lt (less than)
    - Example: 
  
      ```json
      {"field_lt": 25}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field < 25
      ```
    
  - #### lteq (less than or equal to)
    - Example: 
  
      ```json
      {"field_lteq": 25}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field <= 25
      ```

  - #### gt (greater than)
    - Example: 
  
      ```json
      {"field_gt": 25}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field > 25
      ```
    
  - #### gteq (greater than or equal to)
    - Example: 
  
      ```json
      {"field_gteq": 25}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field >= 25
      ```

  - #### matches (matches)
    - Example: 
  
      ```json
      {"field_matches": "Fulano"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field LIKE 'Fulano'
      ```
    
  - #### not_matches (not matches)
    - Example: 
  
      ```json
      {"field_not_matches": "Fulano"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field NOT LIKE 'Fulano'
      ```

  - #### cont (cont)
    - Example: 
  
      ```json
      {"field_cont": "Fulano"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field LIKE '%Fulano%'
      ```

  - #### not_cont (not cont)
    - Example: 
  
      ```json
      {"field_not_cont": "Fulano Silva"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field NOT LIKE '%Fulano%Silva%'
      ```

  - #### start (start)
    - Example: 
  
      ```json
      {"field_start": "Fulano"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field LIKE 'Fulano%'
      ```

  - #### not_start (not start)
    - Example: 
  
      ```json
      {"field_not_start": "Fulano"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field NOT LIKE 'Fulano%'
      ```

  - #### end (end)
    - Example: 
  
      ```json
      {"field_end": "Fulano"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field LIKE '%Fulano'
      ```

  - #### not_end (not end)
    - Example: 
  
      ```json
      {"field_not_end": "Fulano"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field NOT LIKE '%Fulano'
      ```

  - #### between (between)
    - Example: 
  
      ```json
      {"field_between": [10, 20]}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field BETWEEN 10 AND 20
      ```

  - #### order_by (order by)
    - Example: 
  
      ```json
      {"field_order_by": "desc"}
      ```
  
    - SQL result: 
  
      ```sql 
      ORDER BY table.field DESC
      ```


## Authors

- [Marcos Paliari](http://paliari.com.br)
- [Daniel Fernando Lourusso](http://dflourusso.com.br)
