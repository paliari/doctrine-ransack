# doctrine-ransack
#### Requirements

- PHP >= 8.2 (tested on 8.2, 8.3, 8.4 and 8.5)
- Doctrine ORM ^3.7 and DBAL ^4.3

For Doctrine ORM 2.x use the 2.x series (`composer require paliari/doctrine-ransack:^2.3`).
See [Upgrade to 3.0](#upgrade-to-30).

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
            $joinVO->conditionType = Join::ON;
            $joinVO->condition = "$alias.email = $joinVO->alias.email";
            $relationVO->join = $joinVO;

            return $relationVO;
        }

        return null;
    }
}
```

Use `Join::ON` for joins on an entity without association (like above): doctrine/orm deprecates
`Join::WITH` for them.

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

Custom associations are joins without a mapped association, so Doctrine does not nest them in the root
entity. With `includes`, `getArrayResult()` returns alternating rows: the root entity, then the joined
entity (or `null` when nothing matches):

```php
$rows = $ransackBuilder->includes($includes)->getQuery()->getArrayResult();
// [
//     ['id' => 1, 'email' => 'a@a.com'],
//     ['id' => 7, 'name' => 'Ana'],
//     ['id' => 2, 'email' => 'b@b.com'],
//     null,
// ]
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
    Requires exactly 2 values (list or associative array, e.g. `{"from": 10, "to": 20}`);
    any other amount throws `Paliari\Doctrine\Exceptions\RansackException`.
    - Example: 
  
      ```json
      {"col_between": [10, 20]}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE col BETWEEN 10 AND 20
      ```

## Ordering

Use `*_order_by` in `where` or `RansackOrderByVO` in `orderBy`. The direction must be `asc` or `desc`
(case-insensitive); any other value throws `Paliari\Doctrine\Exceptions\RansackException`.

```php
$paramsVO->where = ['person.name_order_by' => 'asc'];
$paramsVO->orderBy = [new RansackOrderByVO(['field' => 'id', 'order' => RansackOrderByVO::DESC])];
```

## Running tests

Each PHP version has a service in `docker-compose.yaml` (`php82`, `php83`, `php84`, `php85`):

```bash
docker compose run --rm php82 composer install
docker compose run --rm php85
docker compose run --rm php85 vendor/bin/phpunit --testsuite Unit
```

## Upgrade to 3.0

1. Requirements: doctrine/orm ^3.7, doctrine/dbal ^4.3 and paliari/php-utils ^3.0 (PHP >= 8.2 unchanged).
   Doctrine ORM 2.x users stay on `^2.3`.
2. `*_between` requires exactly 2 values. A scalar, one value or more than two values now throws
   `RansackException("Between expects 2 values, got N!")` (it used to emit a warning and bind `null`).
   Associative arrays with 2 items (`['from' => 1, 'to' => 2]`) are accepted.
3. `RansackConfig` and `EntityHelper` accept `Doctrine\ORM\EntityManagerInterface` (e.g. decorators).
   Passing an `EntityManager` keeps working; only subclasses relying on the `$em` property type are affected.
4. Order directions are passed to Doctrine as `\SortDirection`, so the DQL always renders `ASC`/`DESC`
   (uppercase) regardless of the case used in `*_order_by` or `RansackOrderByVO`.
5. Custom associations should use `Join::ON` for joins without a mapped association (`Join::WITH` is
   deprecated by doctrine/orm for them).

## Authors

- [Marcos Paliari](http://paliari.com.br)
- [Daniel Fernando Lourusso](http://dflourusso.com.br)
