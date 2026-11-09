# doctrine-ransack

[Português (pt-BR)](README.pt-BR.md)

Advanced search for [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html), inspired by Ruby on
Rails [Ransack](https://activerecord-hackery.github.io/ransack/). You describe a search as a plain array
(usually straight from a query string or JSON body) and doctrine-ransack turns it into joins, `WHERE`,
`ORDER BY` and `GROUP BY` on a Doctrine `QueryBuilder`:

```php
['person.name_cont' => 'Ana Silva', 'email_end' => '@example.com', 'id_order_by' => 'desc']
```

```sql
SELECT u FROM User u LEFT JOIN u.person u_person
WHERE u_person.name LIKE :u_person_name_cont AND u.email LIKE :u_email_end
ORDER BY u.id DESC
-- :u_person_name_cont = '%Ana%Silva%', :u_email_end = '%@example.com'
```

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Building a search](#building-a-search)
- [Filter keys](#filter-keys)
- [Predicates](#predicates)
- [Ordering and grouping](#ordering-and-grouping)
- [Includes (select only what you need)](#includes-select-only-what-you-need)
- [Custom associations](#custom-associations)
- [Security](#security)
- [Errors](#errors)
- [Upgrade to 3.0](#upgrade-to-30)
- [Development](#development)

## Requirements

| doctrine-ransack | PHP    | Doctrine ORM     | Doctrine DBAL               |
|------------------|--------|------------------|-----------------------------|
| 3.x              | >= 8.2 | ^3.7             | ^4.3                        |
| 2.x              | >= 8.2 | ^2.8 or ^3.3     | the one required by the ORM |

Tested on PHP 8.2, 8.3, 8.4 and 8.5. For Doctrine ORM 2.x use the 2.x series
(`composer require paliari/doctrine-ransack:^2.3`).

## Installation

```bash
composer require paliari/doctrine-ransack
```

## Quick start

The examples in this README use these entities: a `User` belongs to a `Person`, which belongs to an `Address`.

```php
#[ORM\Entity]
class User
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public int $id;

    #[ORM\Column]
    public string $email;

    #[ORM\Column]
    public string $password;

    #[ORM\Column(nullable: true)]
    public ?int $person_id;

    #[ORM\ManyToOne(targetEntity: Person::class)]
    public ?Person $person;
}

#[ORM\Entity]
class Person
{
    // id, name, email, document, address_id
    #[ORM\ManyToOne(targetEntity: Address::class)]
    public ?Address $address;
}

#[ORM\Entity]
class Address
{
    // id, street, neighborhood, city, number
}
```

Create one `Ransack` instance (for example as a service) and use it for every search:

```php
use Paliari\Doctrine\Ransack;
use Paliari\Doctrine\RansackConfig;
use Paliari\Doctrine\VO\RansackParamsVO;

$ransack = new Ransack(new RansackConfig($entityManager));

// GET /users?q[person.name_cont]=Ana Silva&q[email_end]=@example.com&q[id_order_by]=desc
$paramsVO = new RansackParamsVO(['where' => $_GET['q'] ?? []]);

$qb = $entityManager->createQueryBuilder()->from(User::class, 'u');
$users = $ransack->query($qb, User::class, 'u')
    ->where($paramsVO)
    ->includes()
    ->getQuery()
    ->getResult();
```

Generated DQL:

```sql
SELECT u FROM User u LEFT JOIN u.person u_person
WHERE u_person.name LIKE :u_person_name_cont AND u.email LIKE :u_email_end
ORDER BY u.id DESC
```

Before passing request data to `where`, read [Security](#security).

## Building a search

### `RansackParamsVO`

| Property  | Type                 | Description                                               |
|-----------|----------------------|-----------------------------------------------------------|
| `where`   | `array`              | Filters, see [Filter keys](#filter-keys)                  |
| `orderBy` | `RansackOrderByVO[]` | Sort fields, see [Ordering and grouping](#ordering-and-grouping) |
| `groupBy` | `string[]`           | Fields to group by (`'person.address.city'`)              |

It can be filled by properties or through the constructor:

```php
$paramsVO = new RansackParamsVO([
    'where' => ['person.address.city_eq' => 'Maringá'],
    'groupBy' => ['person.address.city'],
]);
```

`orderBy` items must be `RansackOrderByVO` instances; plain arrays are not converted.

### Running the query

`Ransack::query(QueryBuilder $qb, string $entityName, string $alias = 't')` returns a `RansackBuilder`.
The `QueryBuilder` must already have the `FROM` with the same entity and alias.

| `RansackBuilder` method | Description |
|-------------------------|-------------|
| `where(RansackParamsVO $paramsVO)` | Adds the joins, conditions, `ORDER BY` and `GROUP BY` |
| `includes(array $includes = [])`   | Replaces the `SELECT` (see [Includes](#includes-select-only-what-you-need)) |
| `getQueryBuilder()`                | The same `QueryBuilder` you passed in |
| `getQuery()`                       | Shortcut for `getQueryBuilder()->getQuery()` |

`where()` does not touch the `SELECT`. Call `includes()` (with no arguments it selects the root entity) or set
the `SELECT` yourself; otherwise Doctrine receives `SELECT FROM User u ...`, which is invalid. Both methods
can be called in any order and more than once.

### Adding your own conditions and pagination

The `QueryBuilder` stays yours: add conditions, using the join aliases created by doctrine-ransack
(`{alias}_{relation}`, see [Joins and aliases](#joins-and-aliases)), and paginate as usual:

```php
use Paliari\Doctrine\VO\RansackOrderByVO;

$paramsVO = new RansackParamsVO();
$paramsVO->where = ['person.address.city_eq' => 'Maringá', 'email_cont' => 'gmail'];
$paramsVO->orderBy = [
    new RansackOrderByVO(['field' => 'person.name', 'order' => RansackOrderByVO::ASC]),
    new RansackOrderByVO(['field' => 'id', 'order' => RansackOrderByVO::DESC]),
];

$qb = $entityManager->createQueryBuilder()->from(User::class, 'u');
$ransack->query($qb, User::class, 'u')->where($paramsVO)->includes();
$qb->andWhere('u_person.document IS NOT NULL')
    ->setFirstResult(0)
    ->setMaxResults(20);
```

```sql
SELECT u FROM User u LEFT JOIN u.person u_person LEFT JOIN u_person.address u_person_address
WHERE u_person_address.city = :u_person_address_city_eq AND u.email LIKE :u_email_cont
  AND u_person.document IS NOT NULL
ORDER BY u_person.name ASC, u.id DESC
```

## Filter keys

Each key of `where` is a field followed by a predicate: `{field}_{predicate}`, like `email_eq` or
`person.name_cont`. The field must be a mapped field of the entity (or of a related entity).

### Relations

Fields of related entities can be written with dots (recommended) or underscores. Both generate the same query:

```php
['person.address.city_eq' => 'Maringá']
['person_address_city_eq' => 'Maringá']
```

```sql
SELECT u FROM User u LEFT JOIN u.person u_person LEFT JOIN u_person.address u_person_address
WHERE u_person_address.city = :u_person_address_city_eq
```

Use dots when field or relation names contain underscores, so there is no ambiguity.

### Same predicate on several fields (`_or_`)

Join fields with `_or_` to apply one predicate to all of them, combined with `OR`. Each field is resolved
from the root entity:

```php
['person.name_or_person.document_cont' => 'Silva']
['email_or_person.name_start' => 'ana']
```

```sql
WHERE u_person.name LIKE :u_person_name_cont OR u_person.document LIKE :u_person_document_cont
WHERE u.email LIKE :u_email_start OR u_person.name LIKE :u_person_name_start
```

### `or` / `and` groups

Top-level keys are combined with `AND`. Use the `or` and `and` keys to build groups, nested as deep as you need:

```php
$paramsVO->where = [
    'email_end' => '@example.com',
    'or' => [
        'person.name_start' => 'Ana',
        'and' => [
            'person.address.city_eq' => 'Maringá',
            'person.address.street_cont' => 'Brasil',
        ],
    ],
];
```

```sql
WHERE u.email LIKE :u_email_end
  AND (u_person.name LIKE :u_person_name_start
       OR (u_person_address.city = :u_person_address_city_eq
           AND u_person_address.street LIKE :u_person_address_street_cont))
```

### Empty values are ignored

Keys whose value is `''`, `null` or `[]` are skipped, so an empty search form field does not filter anything.
The exceptions are keys ending in `_null` (`*_null` and `*_not_null`), which are always applied. `0` and
`'0'` are not empty.

```php
['email_cont' => '', 'person.name_eq' => null, 'id_in' => [], 'person_id_eq' => '0', 'person_id_null' => null]
```

```sql
SELECT u FROM User u WHERE u.person_id = :u_person_id_eq AND u.person_id IS NULL
```

### Joins and aliases

- Relations are joined with `LEFT JOIN`, named `{alias}_{relation}` (`u_person`, `u_person_address`).
- A relation used by several filters, by `orderBy`/`groupBy` or by `includes` is joined only once.

### Parameters

Values are always bound as named parameters, named after the field and the predicate
(`:u_email_eq`, `:u_person_name_cont`). When the same field and predicate appear again (for example inside an
`or` group), the next ones get `_1`, `_2`...:

```php
['email_end' => '@example.com', 'or' => ['email_end' => '@example.org', 'person.name_eq' => 'Ana']]
```

```sql
WHERE u.email LIKE :u_email_end AND (u.email LIKE :u_email_end_1 OR u_person.name = :u_person_name_eq)
-- :u_email_end = '%@example.com', :u_email_end_1 = '%@example.org'
```

## Predicates

Generated DQL for each predicate, using the `User u` entity (bound values in the last column):

| Predicate      | Example                                  | DQL                                                 | Bound value              |
|----------------|------------------------------------------|-----------------------------------------------------|--------------------------|
| `_eq`          | `'email_eq' => 'ana@example.com'`        | `u.email = :u_email_eq`                             | `ana@example.com`        |
| `_not_eq`      | `'email_not_eq' => 'ana@example.com'`    | `u.email <> :u_email_not_eq`                        | `ana@example.com`        |
| `_in`          | `'id_in' => [1, 2, 3]`                   | `u.id IN(:u_id_in)`                                 | `[1, 2, 3]`              |
| `_not_in`      | `'id_not_in' => [1, 2, 3]`               | `u.id NOT IN(:u_id_not_in)`                         | `[1, 2, 3]`              |
| `_null`        | `'person_id_null' => null`               | `u.person_id IS NULL`                               |                          |
| `_not_null`    | `'person_id_not_null' => 1`              | `u.person_id IS NOT NULL`                           |                          |
| `_present`     | `'email_present' => 1`                   | `u.email IS NOT NULL AND u.email <> ''`             |                          |
| `_blank`       | `'email_blank' => 1`                     | `u.email IS NULL OR u.email = ''`                   |                          |
| `_lt`          | `'id_lt' => 10`                          | `u.id < :u_id_lt`                                   | `10`                     |
| `_lteq`        | `'id_lteq' => 10`                        | `u.id <= :u_id_lteq`                                | `10`                     |
| `_gt`          | `'id_gt' => 10`                          | `u.id > :u_id_gt`                                   | `10`                     |
| `_gteq`        | `'id_gteq' => 10`                        | `u.id >= :u_id_gteq`                                | `10`                     |
| `_matches`     | `'email_matches' => 'ana%@example.com'`  | `u.email LIKE :u_email_matches`                     | `ana%@example.com`       |
| `_not_matches` | `'email_not_matches' => 'ana%@example.com'` | `u.email NOT LIKE :u_email_not_matches`          | `ana%@example.com`       |
| `_cont`        | `'email_cont' => 'ana silva'`            | `u.email LIKE :u_email_cont`                        | `%ana%silva%`            |
| `_not_cont`    | `'email_not_cont' => 'ana silva'`        | `u.email NOT LIKE :u_email_not_cont`                | `%ana%silva%`            |
| `_start`       | `'email_start' => 'ana'`                 | `u.email LIKE :u_email_start`                       | `ana%`                   |
| `_not_start`   | `'email_not_start' => 'ana'`             | `u.email NOT LIKE :u_email_not_start`               | `ana%`                   |
| `_end`         | `'email_end' => '@example.com'`          | `u.email LIKE :u_email_end`                         | `%@example.com`          |
| `_not_end`     | `'email_not_end' => '@example.com'`      | `u.email NOT LIKE :u_email_not_end`                 | `%@example.com`          |
| `_between`     | `'id_between' => [10, 20]`               | `u.id BETWEEN :u_id_between_x AND :u_id_between_y`  | `10`, `20`               |
| `_order_by`    | `'email_order_by' => 'desc'`             | `ORDER BY u.email DESC`                             |                          |
| `_group_by`    | `'person.name_group_by' => 1`            | `GROUP BY u_person.name`                            |                          |

Notes:

- **`_cont` / `_not_cont`** wrap the value in `%` and also replace spaces with `%`, so `'ana silva'` matches
  `'Ana Maria Silva'` (case sensitivity depends on the database collation).
- **`_matches` / `_not_matches`** use the value as the `LIKE` pattern, as given.
- **`%` and `_` typed by the user are not escaped** in any `LIKE` predicate; they work as wildcards.
- **`_null`, `_not_null`, `_present`, `_blank` and `_group_by`** ignore the value, but it must not be empty
  (use `1`); only keys ending in `_null` also accept `null`. Note that `false` is not empty either:
  `'email_blank' => false` still applies `_blank`. `_present` and `_blank` are meant for string columns.
- **`_in` / `_not_in`** accept a scalar (`'id_in' => 5` becomes `IN(5)`) and convert each value with the
  column type, so `DateTime` objects work on date columns.
- **`_between`** requires exactly 2 values, as a list or an associative array (`['from' => 10, 'to' => 20]`);
  anything else throws `RansackException`.
- Values of the other predicates are bound with the column type, so `DateTime` and `bool` values work
  (`'active_eq' => false`).

## Ordering and grouping

Ordering can be set inside `where` with `{field}_order_by`, or with `orderBy` (applied after the `where`
ordering, in the given order). The direction must be `asc` or `desc`, in any case; any other value throws
`RansackException`, so the direction can safely come from the request.

```php
use Paliari\Doctrine\VO\RansackOrderByVO;

$paramsVO->where = ['person.name_order_by' => 'asc'];
$paramsVO->orderBy = [
    new RansackOrderByVO(['field' => 'id', 'order' => RansackOrderByVO::DESC]),
    new RansackOrderByVO(['field' => 'email']), // ASC by default
];
```

The DQL always renders the direction in uppercase (`ORDER BY u_person.name ASC, u.id DESC, u.email ASC`).

`groupBy` (or `{field}_group_by` in `where`) adds `GROUP BY` and the needed joins. Grouping is usually combined
with an aggregate `SELECT`, which you set yourself instead of calling `includes()`:

```php
$paramsVO = new RansackParamsVO([
    'where' => ['email_end' => '@example.com'],
    'groupBy' => ['person.address.city'],
]);

$qb = $entityManager->createQueryBuilder()->from(User::class, 'u');
$ransack->query($qb, User::class, 'u')->where($paramsVO);
$qb->select('u_person_address.city, COUNT(u.id) AS total');

$rows = $qb->getQuery()->getArrayResult();
// [['city' => 'Curitiba', 'total' => 1], ['city' => 'Maringá', 'total' => 1]]
```

```sql
SELECT u_person_address.city, COUNT(u.id) AS total
FROM User u LEFT JOIN u.person u_person LEFT JOIN u_person.address u_person_address
WHERE u.email LIKE :u_email_end
GROUP BY u_person_address.city
```

## Includes (select only what you need)

`includes()` replaces the `SELECT` of the query:

- `includes()` with no arguments selects the whole root entity (`SELECT u`).
- `only` lists the fields of an entity to load (as a Doctrine partial object); `id` is always added.
  Without `only`, the whole entity is loaded.
- `include` lists relations to load, joined with `LEFT JOIN`. Each one is either a name (whole entity) or
  `name => [only/include]`, nested as deep as you need.

It is mostly useful with `getArrayResult()`, for APIs that return JSON:

```php
$rows = $ransack->query($qb, User::class, 'u')
    ->where(new RansackParamsVO(['where' => ['person.name_cont' => 'Ana', 'id_order_by' => 'asc']]))
    ->includes([
        'only' => ['email'],
        'include' => [
            'person' => [
                'only' => ['name'],
                'include' => ['address'],
            ],
        ],
    ])
    ->getQuery()
    ->getArrayResult();
```

```sql
SELECT partial u.{id, email}, partial u_person.{id, name}, u_person_address
FROM User u LEFT JOIN u.person u_person LEFT JOIN u_person.address u_person_address
WHERE u_person.name LIKE :u_person_name_cont
ORDER BY u.id ASC
```

```php
[
    [
        'id' => 1,
        'email' => 'ana@example.com',
        'person' => [
            'id' => 1,
            'name' => 'Ana Silva',
            'address' => [
                'id' => 1,
                'street' => 'Av. Brasil',
                'neighborhood' => 'Centro',
                'city' => 'Maringá',
                'number' => '100',
            ],
        ],
    ],
]
```

An unknown relation in `include` throws `RansackException("Relation 'x' not found!")`.

## Custom associations

Sometimes two entities are related without a Doctrine association, for example a `User` and a `Person`
that share the same email. A custom association makes such a relation available to filters and
`includes`, as if it were mapped.

Implement `CustomAssociationInterface`. It is called with the entity, the current alias and the relation
name whenever a name is not a mapped association; return a `RelationVO` for the names you handle and `null`
for the others:

```php
use Doctrine\ORM\Query\Expr\Join;
use Paliari\Doctrine\CustomAssociationInterface;
use Paliari\Doctrine\VO\JoinVO;
use Paliari\Doctrine\VO\RelationVO;

class PersonByEmail implements CustomAssociationInterface
{
    public function __invoke(string $entityName, string $alias, string $field): ?RelationVO
    {
        if (User::class !== $entityName || 'contact' !== $field) {
            return null;
        }
        $join = new JoinVO();
        $join->join = Person::class;
        $join->alias = "{$alias}_contact";
        $join->conditionType = Join::ON;
        $join->condition = "$alias.email = $join->alias.email";

        $relation = new RelationVO();
        $relation->entityName = $entityName;
        $relation->fieldName = $field;
        $relation->targetEntity = Person::class;
        $relation->join = $join;

        return $relation;
    }
}

$ransack = new Ransack(new RansackConfig($entityManager, new PersonByEmail()));
```

| `RelationVO`   | Description                                         |
|----------------|-----------------------------------------------------|
| `entityName`   | Entity that owns the relation (`User::class`)       |
| `fieldName`    | Relation name used in the keys (`contact`)          |
| `targetEntity` | Related entity (`Person::class`)                    |
| `join`         | `JoinVO` describing the join                        |

| `JoinVO`        | Description                                                    |
|-----------------|----------------------------------------------------------------|
| `join`          | Entity class (or `alias.association`) to join                  |
| `alias`         | Alias of the joined entity; any unique alias works             |
| `conditionType` | `Join::ON` for entities without association                    |
| `condition`     | Join condition, in DQL                                         |
| `indexBy`       | Optional `INDEX BY`                                            |

Use `Join::ON`: doctrine/orm deprecates `Join::WITH` for joins on an entity without association.

The relation now works like any other:

```php
$paramsVO = new RansackParamsVO(['where' => ['contact.name_cont' => 'Ana', 'id_order_by' => 'asc']]);
```

```sql
SELECT u FROM User u LEFT JOIN Person u_contact ON u.email = u_contact.email
WHERE u_contact.name LIKE :u_contact_name_cont
ORDER BY u.id ASC
```

Doctrine does not nest entities joined without an association into the root entity. With `includes`,
`getArrayResult()` returns alternating rows: the root entity, then the joined entity (or `null` when nothing
matches):

```php
$rows = $ransack->query($qb, User::class, 'u')
    ->where(new RansackParamsVO(['where' => ['id_order_by' => 'asc']]))
    ->includes(['only' => ['email'], 'include' => ['contact' => ['only' => ['name']]]])
    ->getQuery()
    ->getArrayResult();

// [
//     ['id' => 1, 'email' => 'ana@example.com'],
//     ['id' => 1, 'name' => 'Ana Silva'],
//     ['id' => 2, 'email' => 'bruno@example.com'],
//     ['id' => 2, 'name' => 'Bruno Souza'],
//     ['id' => 3, 'email' => 'carla@example.org'],
//     null,
// ]
```

## Security

What doctrine-ransack already does:

- Values are always bound as parameters, never concatenated into the DQL.
- Field and relation names are checked against the Doctrine metadata; unknown names throw `RansackException`.
- Order directions accept only `asc`/`desc`.

What it does not do: decide which fields a user may search. Any mapped field and relation is reachable, so
a request like `q[password_start]=a` lets someone guess password hashes character by character, and
`q[person.document_cont]=...` searches data the user may not be allowed to see. When `where` comes from the
request, keep only the keys you allow:

```php
function allowedFilters(array $where, array $allowed): array
{
    $filtered = [];
    foreach ($where as $key => $value) {
        if (in_array($key, ['or', 'and'], true) && is_array($value)) {
            $filtered[$key] = allowedFilters($value, $allowed);
        } elseif (in_array($key, $allowed, true)) {
            $filtered[$key] = $value;
        }
    }

    return $filtered;
}

$paramsVO = new RansackParamsVO(['where' => allowedFilters($_GET['q'] ?? [], [
    'person.name_cont',
    'email_end',
    'id_order_by',
])]);
// ['person.name_cont' => 'Ana', 'password_start' => 'a', 'or' => ['email_end' => '@x.com', 'password_eq' => 'x']]
// becomes ['person.name_cont' => 'Ana', 'or' => ['email_end' => '@x.com']]
```

Also remember that `%` and `_` in user input work as wildcards in `LIKE` predicates.

## Errors

Invalid searches throw `Paliari\Doctrine\Exceptions\RansackException`. When the search comes from the
request, catch it and answer with a client error (for example HTTP 400).

| Message                                   | Cause                                                      |
|-------------------------------------------|------------------------------------------------------------|
| `Condition 'email_foo' not found!`        | The key does not end with a known predicate (or has none)  |
| `Field 'xyz' not found!`                  | Unknown field on the root entity, or unknown relation path written with underscores |
| `Field 'person.xyz' not found!`           | Unknown field on a related entity (dot notation)           |
| `Target Model 'company' not found!`       | Unknown relation in a dot notation key                     |
| `Relation 'company' not found!`           | Unknown relation in `includes`                             |
| `Order direction 'random' is invalid!`    | Direction other than `asc`/`desc`                          |
| `Between expects 2 values, got 1!`        | `_between` without exactly 2 values                        |

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

## Development

The test suite runs on SQLite in memory, with one Docker service per PHP version in `docker-compose.yaml`
(`php82`, `php83`, `php84`, `php85`):

```bash
docker compose run --rm php82 composer install
docker compose run --rm php85                                       # whole suite
docker compose run --rm php85 vendor/bin/phpunit --testsuite Unit   # or Functional
docker compose run --rm php85 vendor/bin/phpunit --coverage-text    # coverage (pcov)
```

The suite fails on any deprecation, warning or notice.

## Authors

- [Marcos Paliari](http://paliari.com.br)
- [Daniel Fernando Lourusso](http://dflourusso.com.br)

## License

MIT
