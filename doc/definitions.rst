Definitions
===========

Structure of the filter definition array (Container):
-----------------------------------------------------
    - A container can have one of two properties: a condition or a collection.
    - A collection is a collection of containers, each of which also has a condition or collection, and so on to "infinity".
    - In addition, the collection has the ``andOrOperator`` (AND | OR) property to define the condition for the previous collection.
    - A condition is a field, an operator, a value.
    - Nesting conditions in the collection and determines the correct arrangement of brackets in the final SQL query.

**Condition structure:**

.. code-block:: php

    'condition'     => [
         'property'           => 'city.name', // field
         'comparisonOperator' => null, // one of the comparison operators (see below) or null if it is defined in the SQL function
         'value'              => 'united', // the value by which we filter
         'function'           => [ // SQL function applied to the field
             'aggregate'  => false, // if the function is aggregating, then it must be true, otherwise you can not define
             'definition' => 'MY_FUNCTION({property}, {value}) = TRUE', // the function itself. Tokens ``{property}`` and ``{value}`` will be replaced by a field and value
         ],
    ]

**Example:**

.. code-block:: php

    $containerData = [
        'andOrOperator' => null,
        'collection'    => [
            [
                'andOrOperator' => null,
                'condition'     => [
                    'property'           => 'country.name',
                    'comparisonOperator' => 'contains',
                    'value'              => 'land',
                ],
            ],
            [
                'andOrOperator' => 'OR',
                'collection'    => [
                    [
                        'andOrOperator' => null,
                        'condition'     => [
                            'property'           => 'country.name',
                            'comparisonOperator' => 'beginsWith',
                            'value'              => 'united',
                        ],
                    ],
                    [
                        'andOrOperator' => 'AND',
                        'collection'    => [
                            [
                                'andOrOperator' => null,
                                'condition'     => [
                                    'property'           => 'city.name',
                                    'comparisonOperator' => 'endsWith',
                                    'value'              => 'on',
                                    'function'           => [
                                        'aggregate'  => false,
                                        'definition' => 'lower({property})',
                                    ],
                                ],
                            ],
                            [
                                'andOrOperator' => 'OR',
                                'condition'     => [
                                    'property'           => 'city.name',
                                    'comparisonOperator' => 'in',
                                    'value'              => ['boston', 'new york', 'dallas'],
                                    'function'           => [
                                        'aggregate'  => false,
                                        'definition' => 'lower({property})',
                                    ],
                                ],
                            ],
                            [
                                'andOrOperator' => 'OR',
                                'condition'     => [
                                    'property'           => 'city.id',
                                    'comparisonOperator' => 'greaterThan',
                                    'value'              => 100,
                                    'function'           => [
                                        'aggregate'  => true,
                                        'definition' => 'count({property})',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

**Supported comparison operators:**

    - **equals** - equals
    - **notEquals** - not equals
    - **contains** - contains
    - **notContains** - not contains
    - **beginsWith** - begins with
    - **endsWith** - ends with
    - **notBeginsWith** - not begins with
    - **notEndsWith** - not ends with
    - **lessThan** - less than
    - **lessThanOrEqual** - less than or equal
    - **greaterThan** - greater than
    - **greaterThanOrEqual** - greater than or equal
    - **isNull** - is null
    - **isNotNull** - is not null
    - **between** - between
    - **notBetween** - not between
    - **in** - in
    - **notIn** - not In
    - **instanceOf** - instanceOf ``for \Doctrine\ORM\QueryBuilder``
    - **notInstanceOf** - not InstanceOf ``for \Doctrine\ORM\QueryBuilder``


Structure of the ``ORDER BY`` definition array:
---------------------------------------------

.. code-block:: php

    $orderBy = [
        [
            'country.name', // field
            'asc', // asc|desc (default asc)
            'lower' // SQL function (optional)
        ],
        [
            'country.population',
            'desc'
        ],
    ];

This definition will generate a SQL code like ``ORDER BY LOWER(country.name) ASC, country.population DESC``


Structure of the ``LIMIT OFFSET`` definition:
-------------------------------------------

.. code-block:: php

    $query
        ->buildWhere($container)
        ->buildOrderBy($orderBy);

    $result = $query->getResult(30, 50); // LIMIT 30 OFFSET 50
