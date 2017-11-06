[![pipeline status](https://gitlab.zeka.guru/root/sps-component/badges/master/pipeline.svg)](https://gitlab.zeka.guru/root/sps-component/commits/master)
[![coverage report](https://gitlab.zeka.guru/root/sps-component/badges/master/coverage.svg)](https://gitlab.zeka.guru/root/sps-component/commits/master)

Zk2\SpsComponent
============


Часто возникает необходимость предоставить конечному пользователю возможность сложной
фильтрации каких-либо данных. Достаточно проблематично бывает правильно расставить скобки во множестве AND/OR,
ещё проблематичнее фильтровать/сортировать по агрегирующей функции.

Компонент предназначен для построения валидных блоков "WHERE", "OFFSET", "LIMIT" and "ORDER BY"
в `Doctrine\DBAL\Query\QueryBuilder` | `Doctrine\ORM\QueryBuilder`.
Так же компонент позволяет использовать агрегирующие функции в блоках "WHERE" and "ORDER BY".

Пояснить лучше на примере. Пусть наше приложение имеет следующий QueryBuilder:

    /** @var Doctrine\DBAL\Query\QueryBuilder|Doctrine\ORM\QueryBuilder $queryBuilder */
    $queryBuilder
         ->select('country, region, city')
         ->from('AppBundle:Country', 'country')
         ->leftJoin('country.region', 'region')
         ->leftJoin('country.cities', 'city');

И пусть наше приложение позволяет пользователю фильтровать по следующим полям:

    'country' => [
        'property' => 'country.name',
        'comparisonOperator' => ['contains', 'beginsWith'],
    ],
    'region' => [
        'property' => 'region.name',
        'comparisonOperator' => ['contains', 'endsWith'],
        'function' => [
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

Тогда из клиентского запроса вида:

     collection[0][condition][property]=country
    &collection[0][condition][comparisonOperator]=contains
    &collection[0][condition][value]=land
    &collection[1][andOrOperator]=OR
    &collection[1][collection][0][condition][property]=country
    &collection[1][collection][0][condition][comparisonOperator]=beginsWith
    &collection[1][collection][0][condition][value]=united
    &collection[1][collection][1][andOrOperator]=AND
    &collection[1][collection][1][collection][0][condition][property]=region
    &collection[1][collection][1][collection][0][condition][comparisonOperator]=endsWith
    &collection[1][collection][1][collection][0][condition][value]=on
    &collection[1][collection][1][collection][1][andOrOperator]=OR
    &collection[1][collection][1][collection][1][condition][property]=cities_count
    &collection[1][collection][1][collection][1][condition][comparisonOperator]=greaterThan
    &collection[1][collection][1][collection][1][condition][value]=20
    &sort[0][0]=cities_count
    &sort[0][1]=DESC
    &sort[1][0]=country
    &sort[1][1]=ASC
    &page=2
    &limit=20
    
Вы можете сформировать массив вида:

    [
        'collection' => [
            [
                'condition' => [
                    'property' => 'country.name',
                    'comparisonOperator' => 'contains',
                    'value' => 'land',
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
                        ],
                    ],
                    [
                        'andOrOperator' => 'AND',
                        'collection' => [
                            [
                                'andOrOperator' => null,
                                'condition' => [
                                    'property' => 'region.name',
                                    'comparisonOperator' => 'endsWith',
                                    'value' => 'on',
                                    'function' => [
                                        'aggregate' => false,
                                        'definition' => 'LOWER({property})',
                                    ],
                                ],
                            ],
                            [
                                'andOrOperator' => 'OR',
                                'condition' => [
                                    'property' => 'city.id',
                                    'comparisonOperator' => 'in',
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
    ]

Demo
----
[https://sf.zeka.pp.ua](https://sf.zeka.pp.ua)

Documentation
-------------

[Quick start](https://github.com/zk2/SPSBundle/blob/dev/Resources/doc/index.rst)

[Custom settings](https://github.com/zk2/SPSBundle/blob/dev/Resources/doc/settings.rst)

[Usage](https://github.com/zk2/SPSBundle/blob/dev/Resources/doc/usage.rst)

[Column options](https://github.com/zk2/SPSBundle/blob/dev/Resources/doc/column_options.rst)

[Filter options](https://github.com/zk2/SPSBundle/blob/dev/Resources/doc/filter_options.rst)

Running the Tests
-----------------

Install the [Composer](http://getcomposer.org/) `dev` dependencies:

    php composer.phar install --dev

Then, run the test suite using
[PHPUnit](https://github.com/sebastianbergmann/phpunit/):

    phpunit

License
-------

This bundle is released under the MIT license. See the complete license in the bundle:

    Resources/meta/LICENSE
    
