# doctrine-ransack
#### Installation
	
	$ composer require paliari/doctrine-ransack

#### Configuration

Your setup, example

```php
<?php
use Paliari\Doctrine\Ransack;
use Paliari\Doctrine\RansackConfig;

$ransack = new Ransack(new RansackConfig($entityManager));

```
### Usage

```php
<?php
use Paliari\Doctrine\Ransack;
use Paliari\Doctrine\RansackConfig;
use Paliari\Doctrine\VO\RansackOrderByVO;
use Paliari\Doctrine\VO\RansackParamsVO;

$entityName = User::class;
$alias = 't';
$paramsVO = new RansackParamsVO();
$paramsVO->where = [
    'person.address.street_cont' => 'Av% Brasil',
    'or' => [
        'name_eq' => 'Jhon',
        'email_start' => 'jhon',
        'person.address.city_eq' => 'Maringá',
    ],
    'id_order_by' => 'asc',
];
$paramsVO->orderBy = [
    new RansackOrderByVO(['field' => 'person.name', 'order' => 'ASC']),
    new RansackOrderByVO(['field' => 'person.id', 'order' => 'DESC']),
];
$paramsVO->groupBy = [
    'person.name',
    'person.address_id',
];
$qb = $entityManager->createQueryBuilder()->from($entityName, $alias);
$ransackBuilder = $this->ransack
    ->query($qb, $entityName, $alias)
    ->includes()
    ->where($paramsVO);
$users = $ransackBuilder->getQuery()->getResult();

// Using includes
$includes = [
  'only' => ['id', 'email'],
  'include' => [
    'person' ['only' => ['id', 'name']],
  ],
];
$rows = $ransackBuilder->includes($includes)->getArrayResult();


```
### Custom Association

Your class of get custom association

```php
<?php

use Doctrine\ORM\Query\Expr\Join;
use Paliari\Doctrine\CustomAssociationInterface;
use Paliari\Doctrine\VO\RelationVO;
use Paliari\Doctrine\VO\JoinVO;
use Person;
use User;

class CustomAssociation implements CustomAssociationInterface
{
    public function __invoke(string $entityName, string $alias, string $field): ?RelationVO
    {
        if (User::class === $entityName && 'custom' == $field) {
            $relationVO = new RelationVO();
            $relationVO->entityName = $entityName;
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
$config = new RansackConfig($entityManager, $customAssociation);
$ransack = new Ransack($config);

$entityName = User::class;
$alias = 't';
$paramsVO = new RansackParamsVO();
$paramsVO->where = [
    'custom.email_eq' => 'your-email@gmail.com',
];
$includes = [
  'only' => ['id', 'email'],
  'include' => [
    'custom' ['only' => ['id', 'name']],
  ],
];
$qb = $entityManager->createQueryBuilder()->from($entityName, $alias);
$ransackBuilder = $this->ransack
    ->query($qb, $entityName, $alias)
    ->includes()
    ->where($paramsVO);
$users = $ransackBuilder->getQuery()->getResult();

```

## Filters

The filters must be passed in a hash with the name of
the key containing the field ending with the predicates
below ex: `person.name_eq`, `person.id_gt`.

It is also possible to combine predicates within `or` or `and` clauses, eg:

```php
$where = [
    'name_cont' => 'Jhon',
    'or' => [
        'person.name_start' => 'Jhon',
        'person.email_end' => '@gmail.com',
        'and' => [
            'person.address.city_eq' => 'Maringá',
            'person.address.state_eq' => 'PR',
        ],
    ],
];

```


### List of all possible predicates

  - #### *_eq (equal)
    - Example: 
  
      ```json
      {"col_eq": "Fulano da Silva"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col = 'Fulano da Silva'
      ```
    
  - #### *_not_eq (not equal)
    - Example: 
  
      ```json
      {"col_not_eq": "Fulano da Silva"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col <> 'Fulano da Silva'
      ```
    
  - #### *_in (match any values in array)
    - Example: 
  
      ```json
      {"col_in": [13, 21, 124, 525]}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col IN (13, 21, 124, 525)
      ```
    
  - #### *_not_in (match none of values in array)
    - Example: 
  
      ```json
      {"col_not_in": [13, 21, 124, 525]}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col NOT IN (13, 21, 124, 525)
      ```

  - #### *_null (is null)
    - Example: 
  
      ```json
      {"col_null": null}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col IS NULL
      ```

  - #### *_not_null (is not null)
    - Example: 
  
      ```json
      {"col_not_null": null}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col IS NOT NULL
      ```

  - #### *_present (not null and not empty)
    Only compatible with string columns.
    - Example: 
  
      ```json
      {"col_present": 1}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col IS NOT NULL AND col != ''
      ```

  - #### *_blank (is null or empty)
    Only compatible with string columns.
    - Example: 
  
      ```json
      {"col_blank": 1}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col IS NULL OR col = ''
      ```

  - #### *_lt (less than)
    - Example: 
  
      ```json
      {"col_lt": 25}
      ```

    - SQL result: 
  
      ```sql 
      WHERE col < 25
      ```

  - #### *_lteq (less than or equal to)
    - Example: 

      ```json
      {"col_lteq": 25}
      ```

    - SQL result: 
  
      ```sql 
      WHERE col <= 25
      ```

  - #### *_gt (greater than)
    - Example: 
  
      ```json
      {"col_gt": 25}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col > 25
      ```
    
  - #### *_gteq (greater than or equal to)
    - Example: 
  
      ```json
      {"col_gteq": 25}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col >= 25
      ```

  - #### *_matches (matches with `LIKE`)
    - Example: 
  
      ```json
      {"col_matches": "Fulano"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col LIKE 'Fulano'
      ```
    
  - #### *_not_matches (does not match with `LIKE`)
    - Example: 
  
      ```json
      {"col_not_matches": "Fulano"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col NOT LIKE 'Fulano'
      ```

  - #### *_cont (contains value)
    - Example: 
  
      ```json
      {"col_cont": "Fulano"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col LIKE '%Fulano%'
      ```

  - #### *_not_cont (does not contain)
    - Example: 
  
      ```json
      {"col_not_cont": "Fulano Silva"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col NOT LIKE '%Fulano%Silva%'
      ```

  - #### *_start (starts with)
    - Example: 
  
      ```json
      {"col_start": "Fulano"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col LIKE 'Fulano%'
      ```

  - #### *_not_start (does not start with)
    - Example: 
  
      ```json
      {"col_not_start": "Fulano"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col NOT LIKE 'Fulano%'
      ```

  - #### *_end (ends with)
    - Example: 
  
      ```json
      {"col_end": "Fulano"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col LIKE '%Fulano'
      ```

  - #### *_not_end (does not end with)
    - Example: 
  
      ```json
      {"col_not_end": "Fulano"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col NOT LIKE '%Fulano'
      ```

  - #### *_between (between in 2 values)
    - Example: 
  
      ```json
      {"col_between": [10, 20]}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col BETWEEN 10 AND 20
      ```

## Authors

- [Marcos Paliari](http://paliari.com.br)
- [Daniel Fernando Lourusso](http://dflourusso.com.br)
