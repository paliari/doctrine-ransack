# doctrine-ransack
#### Installation
	
	$ composer require paliari/doctrine-ransack

#### Configuration

Your repository class extends to AbstractRansackRepository, example

```php
<?php

// Create your repository extended to AbstractRansackRepository.
class YourRepository extends \Paliari\Doctrine\AbstractRansackRepository
{

    protected static function modelName(): string
    {
        return 'MyModel';
    }

    /**
     * Override the method getEm is required.
     * @return \Doctrine\ORM\EntityManager
     */
    public static function getEm()
    {
        // return EntityManager
    }

}

```
### Usage

```php
<?php

$params = [
    'id_lteq'        => 20,
    'email_not_null' => null,
    'person_name_eq' => 'abc',
];
$qb = YourRepository::ransack($params);
$rows = $qb->getQuery()->getArrayResult();

// Using includes
$includes = [
    'only' => ['id', 'email'],
    'include' => [
        'person' => [
            'only' => ['id', 'name']
        ]
    ]
];
$rows = $qb->includes($includes)->getArrayResult();


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
    
  - #### cont (cont)
    - Example: 
  
      ```json
      {"field_cont": "Fulano"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field LIKE '%Fulano%'
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

  - #### end (end)
    - Example: 
  
      ```json
      {"field_end": "Fulano"}
      ```
  
    - SQL result: 
  
      ```sql 
      WHERE table.field LIKE '%Fulano'
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
