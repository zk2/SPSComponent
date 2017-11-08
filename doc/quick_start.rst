Let our application have the following QueryBuilder:

.. code-block:: php

    /** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
    $queryBuilder
         ->select('country, city')
         ->from('AppBundle:Country', 'country')
         ->leftJoin('country.cities', 'city');

And we would like to get something like this pseudo-SQL code:

.. code-block:: sql

    SELECT * FROM country,city
    WHERE LOWER(country.name) LIKE '%land%' OR (LOWER(country.name) LIKE 'united%' AND COUNT(city.id) >= 20)

First you need to define fields for filtering:

.. code-block:: php

    'country' => [
        'property' => 'country.name',
        'comparisonOperator' => ['contains', 'beginsWith', 'endsWith'], // supported operators
        'function' => [ // SQL function applied to the field (optional)
            'aggregate' => false,
            'definition' => 'LOWER({property})',
        ],
    ],
    'cities_count' => [
        'property' => 'city.id',
        'comparisonOperator' => ['equals', 'greaterThan', 'lessThan'],
        'function' => [
            'aggregate' => true,
            'definition' => 'COUNT({property})',
        ],
    ],

Then, having imposed a client request like this

.. code-block:: php

    collection[0][condition][property]=country
    &collection[0][condition][comparisonOperator]=contains
    &collection[0][condition][value]=land
    &collection[1][andOrOperator]=OR
    &collection[1][collection][0][condition][property]=country
    &collection[1][collection][0][condition][comparisonOperator]=beginsWith
    &collection[1][collection][0][condition][value]=united
    &collection[1][collection][1][andOrOperator]=AND
    &collection[1][collection][1][collection][0][condition][property]=cities_count
    &collection[1][collection][1][collection][0][condition][comparisonOperator]=greaterThan
    &collection[1][collection][1][collection][0][condition][value]=20

on the array of definitions, we get an array like this:

.. code-block:: php

    $data = [
        'collection' => [
            [
                'condition' => [
                    'property' => 'country.name',
                    'comparisonOperator' => 'contains',
                    'value' => 'land',
                    'function' => [
                        'aggregate' => false,
                        'definition' => 'LOWER({property})',
                    ],
                ],
            ],
            [
                'andOrOperator' => 'OR',
                'collection' => [
                    [
                        'andOrOperator' => null,
                        'condition' => [
                            'property' => 'country.name',
                            'comparisonOperator' => 'beginsWith',
                            'value' => 'united',
                            'function' => [
                                'aggregate' => false,
                                'definition' => 'LOWER({property})',
                            ],
                        ],
                    ],
                    [
                        'andOrOperator' => 'AND',
                        'collection' => [
                            [
                                'andOrOperator' => null,
                                'condition' => [
                                    'property' => 'city.id',
                                    'comparisonOperator' => 'greaterThan',
                                    'value' => 20,
                                    'function' => [
                                        'aggregate' => true,
                                        'definition' => 'COUNT({property})',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

The final code will be like this:

.. code-block:: php

    $container = \Zk2\SpsComponent\Condition\Container::create($data);
    $query = \Zk2\SpsComponent\QueryBuilderFactory::createQueryBuilder($queryBuilder);
    $query
        ->buildWhere($container)
        ->buildOrderBy(['country.name', 'asc']);

    $result = $query->getResult($limit, $offset);
