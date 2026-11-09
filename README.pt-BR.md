# doctrine-ransack

[English](README.md)

Busca avançada para o [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html), inspirada no
[Ransack](https://activerecord-hackery.github.io/ransack/) do Ruby on Rails. Você descreve a busca como um
array simples (normalmente vindo direto da query string ou de um corpo JSON) e o doctrine-ransack o transforma
em joins, `WHERE`, `ORDER BY` e `GROUP BY` num `QueryBuilder` do Doctrine:

```php
['person.name_cont' => 'Ana Silva', 'email_end' => '@example.com', 'id_order_by' => 'desc']
```

```sql
SELECT u FROM User u LEFT JOIN u.person u_person
WHERE u_person.name LIKE :u_person_name_cont AND u.email LIKE :u_email_end
ORDER BY u.id DESC
-- :u_person_name_cont = '%Ana%Silva%', :u_email_end = '%@example.com'
```

## Conteúdo

- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Começando](#começando)
- [Montando uma busca](#montando-uma-busca)
- [Chaves de filtro](#chaves-de-filtro)
- [Predicados](#predicados)
- [Ordenação e agrupamento](#ordenação-e-agrupamento)
- [Includes (selecionar só o necessário)](#includes-selecionar-só-o-necessário)
- [Associações customizadas](#associações-customizadas)
- [Segurança](#segurança)
- [Erros](#erros)
- [Migrando para a 3.0](#migrando-para-a-30)
- [Desenvolvimento](#desenvolvimento)

## Requisitos

| doctrine-ransack | PHP    | Doctrine ORM     | Doctrine DBAL         |
|------------------|--------|------------------|-----------------------|
| 3.x              | >= 8.2 | ^3.7             | ^4.3                  |
| 2.x              | >= 8.2 | ^2.8 ou ^3.3     | a exigida pelo ORM    |

Testado no PHP 8.2, 8.3, 8.4 e 8.5. Para o Doctrine ORM 2.x, use a série 2.x
(`composer require paliari/doctrine-ransack:^2.3`).

## Instalação

```bash
composer require paliari/doctrine-ransack
```

## Começando

Os exemplos deste README usam estas entidades: um `User` pertence a uma `Person`, que pertence a um `Address`.

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

Crie uma instância de `Ransack` (por exemplo, como serviço) e use-a em todas as buscas:

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

DQL gerado:

```sql
SELECT u FROM User u LEFT JOIN u.person u_person
WHERE u_person.name LIKE :u_person_name_cont AND u.email LIKE :u_email_end
ORDER BY u.id DESC
```

Antes de passar dados da requisição para o `where`, leia [Segurança](#segurança).

## Montando uma busca

### `RansackParamsVO`

| Propriedade | Tipo                 | Descrição                                                   |
|-------------|----------------------|-------------------------------------------------------------|
| `where`     | `array`              | Filtros, veja [Chaves de filtro](#chaves-de-filtro)         |
| `orderBy`   | `RansackOrderByVO[]` | Campos de ordenação, veja [Ordenação e agrupamento](#ordenação-e-agrupamento) |
| `groupBy`   | `string[]`           | Campos de agrupamento (`'person.address.city'`)             |

Pode ser preenchido pelas propriedades ou pelo construtor:

```php
$paramsVO = new RansackParamsVO([
    'where' => ['person.address.city_eq' => 'Maringá'],
    'groupBy' => ['person.address.city'],
]);
```

Os itens de `orderBy` precisam ser instâncias de `RansackOrderByVO`; arrays simples não são convertidos.

### Executando a query

`Ransack::query(QueryBuilder $qb, string $entityName, string $alias = 't')` retorna um `RansackBuilder`.
O `QueryBuilder` já precisa ter o `FROM` com a mesma entidade e o mesmo alias.

| Método do `RansackBuilder`         | Descrição |
|------------------------------------|-----------|
| `where(RansackParamsVO $paramsVO)` | Adiciona joins, condições, `ORDER BY` e `GROUP BY` |
| `includes(array $includes = [])`   | Substitui o `SELECT` (veja [Includes](#includes-selecionar-só-o-necessário)) |
| `getQueryBuilder()`                | O mesmo `QueryBuilder` que você passou |
| `getQuery()`                       | Atalho para `getQueryBuilder()->getQuery()` |

O `where()` não mexe no `SELECT`. Chame `includes()` (sem argumentos ele seleciona a entidade raiz) ou defina o
`SELECT` você mesmo; caso contrário o Doctrine recebe `SELECT FROM User u ...`, que é inválido. Os dois métodos
podem ser chamados em qualquer ordem e mais de uma vez.

### Adicionando suas próprias condições e paginação

O `QueryBuilder` continua sendo seu: adicione condições usando os aliases dos joins criados pelo
doctrine-ransack (`{alias}_{relação}`, veja [Joins e aliases](#joins-e-aliases)) e pagine como de costume:

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

## Chaves de filtro

Cada chave do `where` é um campo seguido de um predicado: `{campo}_{predicado}`, como `email_eq` ou
`person.name_cont`. O campo precisa ser um campo mapeado da entidade (ou de uma entidade relacionada).

### Relações

Campos de entidades relacionadas podem ser escritos com pontos (recomendado) ou com underlines. Os dois geram
a mesma query:

```php
['person.address.city_eq' => 'Maringá']
['person_address_city_eq' => 'Maringá']
```

```sql
SELECT u FROM User u LEFT JOIN u.person u_person LEFT JOIN u_person.address u_person_address
WHERE u_person_address.city = :u_person_address_city_eq
```

Use pontos quando nomes de campos ou relações tiverem underline, para não haver ambiguidade.

### Mesmo predicado em vários campos (`_or_`)

Junte campos com `_or_` para aplicar um predicado a todos eles, combinados com `OR`. Cada campo é resolvido a
partir da entidade raiz:

```php
['person.name_or_person.document_cont' => 'Silva']
['email_or_person.name_start' => 'ana']
```

```sql
WHERE u_person.name LIKE :u_person_name_cont OR u_person.document LIKE :u_person_document_cont
WHERE u.email LIKE :u_email_start OR u_person.name LIKE :u_person_name_start
```

### Grupos `or` / `and`

As chaves do primeiro nível são combinadas com `AND`. Use as chaves `or` e `and` para montar grupos, aninhados
quantos níveis precisar:

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

### Valores vazios são ignorados

Chaves com valor `''`, `null` ou `[]` são ignoradas, então um campo vazio do formulário de busca não filtra nada.
As exceções são as chaves terminadas em `_null` (`*_null` e `*_not_null`), que são sempre aplicadas. `0` e `'0'`
não são vazios.

```php
['email_cont' => '', 'person.name_eq' => null, 'id_in' => [], 'person_id_eq' => '0', 'person_id_null' => null]
```

```sql
SELECT u FROM User u WHERE u.person_id = :u_person_id_eq AND u.person_id IS NULL
```

### Joins e aliases

- As relações entram com `LEFT JOIN`, com o nome `{alias}_{relação}` (`u_person`, `u_person_address`).
- Uma relação usada por vários filtros, por `orderBy`/`groupBy` ou pelo `includes` entra uma vez só.

### Parâmetros

Os valores sempre viram parâmetros nomeados, com o nome do campo e do predicado (`:u_email_eq`,
`:u_person_name_cont`). Quando o mesmo campo e predicado aparecem de novo (por exemplo, dentro de um grupo `or`),
os seguintes recebem `_1`, `_2`...:

```php
['email_end' => '@example.com', 'or' => ['email_end' => '@example.org', 'person.name_eq' => 'Ana']]
```

```sql
WHERE u.email LIKE :u_email_end AND (u.email LIKE :u_email_end_1 OR u_person.name = :u_person_name_eq)
-- :u_email_end = '%@example.com', :u_email_end_1 = '%@example.org'
```

## Predicados

DQL gerado por cada predicado, usando a entidade `User u` (o valor enviado como parâmetro fica na última coluna):

| Predicado      | Exemplo                                  | DQL                                                 | Valor do parâmetro       |
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

Observações:

- **`_cont` / `_not_cont`** envolvem o valor em `%` e também trocam espaços por `%`, então `'ana silva'`
  encontra `'Ana Maria Silva'` (diferenciar maiúsculas depende da collation do banco).
- **`_matches` / `_not_matches`** usam o valor como padrão do `LIKE`, do jeito que vier.
- **`%` e `_` digitados pelo usuário não são escapados** em nenhum predicado com `LIKE`; funcionam como curingas.
- **`_null`, `_not_null`, `_present`, `_blank` e `_group_by`** ignoram o valor, mas ele não pode ser vazio
  (use `1`); só as chaves terminadas em `_null` aceitam também `null`. Atenção: `false` também não é vazio,
  então `'email_blank' => false` aplica o `_blank` mesmo assim. `_present` e `_blank` são para colunas de texto.
- **`_in` / `_not_in`** aceitam um escalar (`'id_in' => 5` vira `IN(5)`) e convertem cada valor pelo tipo da
  coluna, então objetos `DateTime` funcionam em colunas de data.
- **`_between`** exige exatamente 2 valores, em lista ou array associativo (`['from' => 10, 'to' => 20]`);
  qualquer outra coisa lança `RansackException`.
- Os valores dos outros predicados são enviados com o tipo da coluna, então valores `DateTime` e `bool`
  funcionam (`'active_eq' => false`).

## Ordenação e agrupamento

A ordenação pode ser definida dentro do `where`, com `{campo}_order_by`, ou com `orderBy` (aplicado depois da
ordenação do `where`, na ordem informada). A direção precisa ser `asc` ou `desc`, em maiúsculas ou minúsculas;
qualquer outro valor lança `RansackException`, então a direção pode vir da requisição com segurança.

```php
use Paliari\Doctrine\VO\RansackOrderByVO;

$paramsVO->where = ['person.name_order_by' => 'asc'];
$paramsVO->orderBy = [
    new RansackOrderByVO(['field' => 'id', 'order' => RansackOrderByVO::DESC]),
    new RansackOrderByVO(['field' => 'email']), // ASC por padrão
];
```

O DQL sempre mostra a direção em maiúsculas (`ORDER BY u_person.name ASC, u.id DESC, u.email ASC`).

O `groupBy` (ou `{campo}_group_by` no `where`) adiciona o `GROUP BY` e os joins necessários. Agrupamento
normalmente vem junto de um `SELECT` com agregação, que você define no lugar de chamar `includes()`:

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

## Includes (selecionar só o necessário)

O `includes()` substitui o `SELECT` da query:

- `includes()` sem argumentos seleciona a entidade raiz inteira (`SELECT u`).
- `only` lista os campos da entidade que serão carregados (como partial object do Doctrine); o `id` é sempre
  incluído. Sem `only`, a entidade inteira é carregada.
- `include` lista as relações a carregar, com `LEFT JOIN`. Cada item é um nome (entidade inteira) ou
  `nome => [only/include]`, aninhado quantos níveis precisar.

É mais útil com `getArrayResult()`, em APIs que devolvem JSON:

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

Uma relação desconhecida no `include` lança `RansackException("Relation 'x' not found!")`.

## Associações customizadas

Às vezes duas entidades se relacionam sem uma associação do Doctrine, por exemplo um `User` e uma `Person` que
têm o mesmo email. Uma associação customizada torna essa relação disponível para filtros e para o `includes`,
como se ela fosse mapeada.

Implemente `CustomAssociationInterface`. Ela é chamada com a entidade, o alias atual e o nome da relação sempre
que um nome não for uma associação mapeada; devolva um `RelationVO` para os nomes que você trata e `null` para
os outros:

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

| `RelationVO`   | Descrição                                          |
|----------------|----------------------------------------------------|
| `entityName`   | Entidade dona da relação (`User::class`)           |
| `fieldName`    | Nome da relação usado nas chaves (`contact`)       |
| `targetEntity` | Entidade relacionada (`Person::class`)             |
| `join`         | `JoinVO` que descreve o join                       |

| `JoinVO`        | Descrição                                                    |
|-----------------|--------------------------------------------------------------|
| `join`          | Classe da entidade (ou `alias.associação`) a juntar          |
| `alias`         | Alias da entidade juntada; qualquer alias único funciona     |
| `conditionType` | `Join::ON` para entidades sem associação                     |
| `condition`     | Condição do join, em DQL                                     |
| `indexBy`       | `INDEX BY` opcional                                          |

Use `Join::ON`: o doctrine/orm deprecia `Join::WITH` em joins com entidade sem associação.

A relação passa a funcionar como qualquer outra:

```php
$paramsVO = new RansackParamsVO(['where' => ['contact.name_cont' => 'Ana', 'id_order_by' => 'asc']]);
```

```sql
SELECT u FROM User u LEFT JOIN Person u_contact ON u.email = u_contact.email
WHERE u_contact.name LIKE :u_contact_name_cont
ORDER BY u.id ASC
```

O Doctrine não aninha na entidade raiz as entidades juntadas sem associação. Com `includes`, o
`getArrayResult()` devolve linhas alternadas: a entidade raiz e depois a entidade juntada (ou `null` quando não
há correspondência):

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

## Segurança

O que o doctrine-ransack já faz:

- Os valores sempre viram parâmetros, nunca são concatenados no DQL.
- Nomes de campos e relações são validados contra o metadata do Doctrine; nomes desconhecidos lançam
  `RansackException`.
- A direção da ordenação só aceita `asc`/`desc`.

O que ele não faz: decidir quais campos um usuário pode buscar. Qualquer campo e relação mapeados ficam
acessíveis, então uma requisição como `q[password_start]=a` permite adivinhar hashes de senha caractere por
caractere, e `q[person.document_cont]=...` busca dados que o usuário talvez não possa ver. Quando o `where` vier
da requisição, mantenha só as chaves permitidas:

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
// vira ['person.name_cont' => 'Ana', 'or' => ['email_end' => '@x.com']]
```

Lembre também que `%` e `_` vindos do usuário funcionam como curingas nos predicados com `LIKE`.

## Erros

Buscas inválidas lançam `Paliari\Doctrine\Exceptions\RansackException`. Quando a busca vem da requisição,
capture a exceção e responda com erro do cliente (por exemplo, HTTP 400).

| Mensagem                                  | Causa                                                      |
|-------------------------------------------|------------------------------------------------------------|
| `Condition 'email_foo' not found!`        | A chave não termina com um predicado conhecido (ou não tem predicado) |
| `Field 'xyz' not found!`                  | Campo desconhecido na entidade raiz, ou caminho de relação desconhecido escrito com underline |
| `Field 'person.xyz' not found!`           | Campo desconhecido numa entidade relacionada (notação com ponto) |
| `Target Model 'company' not found!`       | Relação desconhecida numa chave com ponto                  |
| `Relation 'company' not found!`           | Relação desconhecida no `includes`                         |
| `Order direction 'random' is invalid!`    | Direção diferente de `asc`/`desc`                          |
| `Between expects 2 values, got 1!`        | `_between` sem exatamente 2 valores                        |

## Migrando para a 3.0

1. Requisitos: doctrine/orm ^3.7, doctrine/dbal ^4.3 e paliari/php-utils ^3.0 (PHP >= 8.2, sem mudança).
   Quem usa Doctrine ORM 2.x continua na `^2.3`.
2. `*_between` exige exatamente 2 valores. Um escalar, um valor ou mais de dois valores agora lançam
   `RansackException("Between expects 2 values, got N!")` (antes gerava um warning e enviava `null`).
   Arrays associativos com 2 itens (`['from' => 1, 'to' => 2]`) são aceitos.
3. `RansackConfig` e `EntityHelper` aceitam `Doctrine\ORM\EntityManagerInterface` (por exemplo, decorators).
   Passar um `EntityManager` continua funcionando; só subclasses que dependem do tipo da propriedade `$em` são
   afetadas.
4. As direções de ordenação são passadas ao Doctrine como `\SortDirection`, então o DQL sempre mostra
   `ASC`/`DESC` (maiúsculo), independente de como foi escrito em `*_order_by` ou `RansackOrderByVO`.
5. Associações customizadas devem usar `Join::ON` em joins sem associação mapeada (o doctrine/orm deprecia
   `Join::WITH` nesses casos).

## Desenvolvimento

A suíte de testes roda em SQLite em memória, com um serviço Docker por versão de PHP no `docker-compose.yaml`
(`php82`, `php83`, `php84`, `php85`):

```bash
docker compose run --rm php82 composer install
docker compose run --rm php85                                       # suíte completa
docker compose run --rm php85 vendor/bin/phpunit --testsuite Unit   # ou Functional
docker compose run --rm php85 vendor/bin/phpunit --coverage-text    # cobertura (pcov)
```

A suíte falha com qualquer deprecation, warning ou notice.

## Autores

- [Marcos Paliari](http://paliari.com.br)
- [Daniel Fernando Lourusso](http://dflourusso.com.br)

## Licença

MIT
