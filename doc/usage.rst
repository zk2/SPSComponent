Usage
=====

As already mentioned, sps-component uses the ``Doctrine\DBAL\Query\QueryBuilder`` or ``Doctrine\ORM\QueryBuilder``.
There are small nuances of using each of them:
    - ``ORM QueryBuilder`` uses DQL and returns array of objects or array of strings.
      In the case of objects, you can use not only properties in the table columns, but also methods
      (used ``Symfony\Component\PropertyAccess\PropertyAccess`` component).
      Among the disadvantages, it should be noted that there is impossible to sort columns which are not associated with the appropriate property in the object.
    - ``DBAL QueryBuilder`` use nature SQL queries. This allows you to use any SQL construct that returns data in a table view.

**Example $data for all cases below**

.. code-block:: php

    $data = [
        'collection' => [
            [
                'condition' => [
                    'property' => 'country.name',
                    'comparisonOperator' => 'contains',
                    'value' => 'land',
                    'sql_function' => [
                        'aggregate' => false,
                        'definition' => 'LOWER({property})',
                    ],
                    'php_function' => [
                        'definition' => 'strtolower',
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
                            'sql_function' => [
                                'aggregate' => false,
                                'definition' => 'LOWER({property})',
                            ],
                            'php_function' => [
                                'definition' => 'strtolower',
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
                                    'sql_function' => [
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

**Example for \Doctrine\ORM\QueryBuilder (objects)**

.. code-block:: php

    /** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
    $queryBuilder
         ->select('country, city')
         ->from('AppBundle:Country', 'country')
         ->leftJoin('country.cities', 'city');

    $orderBy = [
        ['country.name', 'asc', 'lower'],
    ];

    $limit = 30;
    $offset = 50;

    $container = \Zk2\SpsComponent\Condition\Container::create($data);
    $query = \Zk2\SpsComponent\QueryBuilderFactory::createQueryBuilder($queryBuilder);

    // If you need Query::HINT, you should add it like this //
    $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SortableNullsWalker::class);
    $query->setHint('SortableNullsWalker.fields', ['country.name' => SortableNullsWalker::NULLS_LAST]);


    $query
        ->buildWhere($container)
        ->buildOrderBy($orderBy);

    $result = $query->getResult($limit, $offset);

**Example for \Doctrine\DBAL\Query\QueryBuilder**

.. code-block:: php

    /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
    $queryBuilder
        ->select('country.name AS country_name, COUNT(city.id) AS cnt')
        ->from('country', 'country')
        ->leftJoin('country', 'city', 'city', 'city.country_id = country.id')
        ->groupBy('country.name');

    $orderBy = [
        ['cnt', 'desc'],
        ['country.name', 'asc', 'lower'],
    ];

    $limit = 30;
    $offset = 50;

    $container = \Zk2\SpsComponent\Condition\Container::create($data);
    $query = \Zk2\SpsComponent\QueryBuilderFactory::createQueryBuilder($queryBuilder);
    $query
        ->buildWhere($container)
        ->buildOrderBy($orderBy);

    $result = $query->getResult($limit, $offset);
