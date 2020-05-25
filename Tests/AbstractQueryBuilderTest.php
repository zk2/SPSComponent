<?php
/**
 * This file is part of the SpsComponent package.
 *
 * (c) Evgeniy Budanov <budanov.ua@gmail.comm> 2017.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests;

use Doctrine\DBAL\Types\Type;
use Tests\Entity\City;
use Tests\Entity\Continent;
use Tests\Entity\Country;
use Tests\Entity\CountryLanguage;
use Tests\Entity\Region;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Tools\Setup;
use Doctrine\DBAL\Query\QueryBuilder as DBALBuilder;
use Doctrine\ORM\QueryBuilder as ORMBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Zk2\SpsComponent\Condition\ContainerInterface;
use Zk2\SpsComponent\QueryBuilderFactory;
use Zk2\SpsComponent\QueryBuilderInterface;

/**
 * Class AbstractQueryBuilderTest
 */
abstract class AbstractQueryBuilderTest extends TestCase
{
    /**
     * @var EntityManager $em
     */
    protected $em;

    /**
     * @var array $dbParams
     */
    protected $dbParams = ['driver' => 'pdo_sqlite', 'memory' => true];

    /**
     * @var int
     */
    protected $debug = 0;

    /**
     * @var MonologSQLLogger
     */
    protected $logger;

    /**
     * @var array
     */
    protected $orderByData = [
        ['country.name', 'asc'],
        ['country.population', 'asc'],
    ];

    /**
     * @var array
     */
    protected $baseWhereData = [
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
                                    'sql_function'           => [
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
                                    'sql_function'           => [
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
                                    'sql_function'           => [
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

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        ini_set('memory_limit', '512M');
        if (in_array('-v', $_SERVER['argv'], true)) {
            $this->debug = 1;
        } elseif (in_array('-vv', $_SERVER['argv'], true)) {
            $this->debug = 2;
        } elseif (in_array('-vvv', $_SERVER['argv'], true)) {
            $this->debug = 3;
        }

        try {
            $config = Setup::createAnnotationMetadataConfiguration([__DIR__.'/Entity'], true, null, null, false);
            $this->em = EntityManager::create($this->dbParams, $config);
            $this->assertInstanceOf(EntityManager::class, $this->em);
            $this->em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('tsvector', 'string');
            if (!Type::hasType('tsvector')) {
                Type::addType('tsvector', 'Tests\Doctrine\TsvectorType');
                $this->em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping(
                    'db_tsvector',
                    'tsvector'
                );
            }
            $this->loadData();
        } catch (\Exception $e) {
            $this->markTestSkipped('Skipped'.PHP_EOL.$e->getMessage());
        }
    }

    /**
     * @param string $driver
     *
     * @throws \Exception
     */
    protected function preSetUp($driver)
    {
        $prefix = null;
        switch ($driver) {
            case 'pdo_pgsql':
                $prefix = 'PgSql';
                break;
            case 'pdo_mysql':
                $prefix = 'MySql';
                break;
            case 'pdo_sqlite':
                return;
            default:
                throw new \Exception(sprintf('Driver "%s" isn\'t supported', $driver));
        }
        $this->dbParams = [
            'driver'   => $driver,
            'host'     => getenv(sprintf('%s_host', $prefix)),
            'port'     => getenv(sprintf('%s_port', $prefix)),
            'user'     => getenv(sprintf('%s_username', $prefix)),
            'password' => getenv(sprintf('%s_password', $prefix)),
            'dbname'   => getenv(sprintf('%s_database', $prefix)),
        ];
    }

    /**
     * runTestOrmObjectQueryBuilder
     */
    protected function runTestOrmObjectQueryBuilder()
    {
        $this->addToLog('BASE WHERE DATA');
        $container = $this->getContainer($this->baseWhereData);
        $ormQb = $this->getOrmQueryBuilder();
        $ormQb->select('country, continent, region, capital, city');

        $qb = $this->buildOrmQuery($ormQb, $container);

        $result = $this->getCountriesAsArray($qb->getResult(4, 10));

        if ($this->debug > 2) {
            print_r($result);
        }

        $this->assertTrue($qb->totalResultCount() > 0);
        $this->assertTrue(count($result) === 4);
        $this->assertTrue($qb->currentResultCount() === 4);
        $this->assertStringContainsStringIgnoringCase('SELECT', $ormQb->getDQL());
        $this->assertStringContainsStringIgnoringCase('SELECT', $ormQb->getQuery()->getSQL());

        $this->addToLog('IS NULL :: IS NOT NULL');
        $this->orderByData = [];
        foreach (['isNull', 'isNotNull'] as $type) {
            $container = $this->getContainer($this->getSingleCondition('capital.id', $type));
            $ormQb = $this->getOrmQueryBuilder();
            $ormQb->select('country, capital');
            $qb = $this->buildOrmQuery($ormQb, $container);
            /** @var Country[] $result */
            $result = $qb->getResult(2, 0);
            if ($this->debug > 2) {
                print_r($this->getCountriesAsArray($result));
            }
            if ('isNull' === $type) {
                $this->assertTrue(null === $result[0]->getCapital());
                $this->assertTrue(null === $result[1]->getCapital());
            } else {
                $this->assertTrue(null !== $result[0]->getCapital());
                $this->assertTrue(null !== $result[1]->getCapital());
            }
        }
    }

    /**
     * runTestOrmArrayQueryBuilder
     */
    protected function runTestOrmArrayQueryBuilder()
    {
        $this->addToLog('BASE WHERE DATA');
        $container = $this->getContainer($this->baseWhereData);
        $ormQb = $this->getOrmQueryBuilder();
        $ormQb->select(
            'country.name AS country_name, continent.name AS continent_name, region.name AS region_name,
             capital.name AS capital_name, COUNT(city.id) AS cnt'
        )
            ->groupBy('country.id')
            ->addGroupBy('country.name')
            ->addGroupBy('continent.name')
            ->addGroupBy('region.name')
            ->addGroupBy('capital.name');

        $qb = $this->buildOrmQuery($ormQb, $container);

        $result = $qb->getResult(4, 10);

        if ($this->debug > 2) {
            print_r($result);
        }

        $this->assertTrue($qb->totalResultCount() > 0);
        $this->assertTrue(count($result) > 0);
        $this->assertTrue($qb->currentResultCount() === 4);
        $this->assertStringContainsStringIgnoringCase('SELECT', $ormQb->getDQL());
        $this->assertStringContainsStringIgnoringCase('SELECT', $ormQb->getQuery()->getSQL());

        $this->addToLog('IS NULL :: IS NOT NULL');
        $this->orderByData = [];
        foreach (['isNull', 'isNotNull'] as $type) {
            $container = $this->getContainer($this->getSingleCondition('capital.id', $type));
            $ormQb = $this->getOrmQueryBuilder();
            $ormQb->select('country.name AS country_name, capital.name AS capital_name');
            $qb = $this->buildOrmQuery($ormQb, $container);
            /** @var Country[] $result */
            $result = $qb->getResult(2, 0);
            if ($this->debug > 2) {
                print_r($result);
            }
            if ('isNotNull' === $type) {
                $this->assertTrue(null !== $result[0]['capital_name']);
                $this->assertTrue(null !== $result[1]['capital_name']);
            } else {
                $this->assertTrue(null === $result[0]['capital_name']);
                $this->assertTrue(null === $result[1]['capital_name']);
            }
        }

        $this->addToLog('ORDER BY ALIAS');
        foreach (['asc', 'desc'] as $direction) {
            $this->orderByData = [['country_name', $direction]];
            $container = $this->getContainer([]);
            $ormQb = $this->getOrmQueryBuilder();
            $ormQb->select(['country.name AS country_name, region.name region_name', 'continent.name continent_name']);
            $ormQb->addSelect(['country.id']);
            $qb = $this->buildOrmQuery($ormQb, $container);
            /** @var Country[] $result */
            $result = $qb->getResult(2, 0);
            if ($this->debug > 2) {
                print_r($result);
            }
            if ('asc' === $direction) {
                $this->assertTrue(stripos($result[0]['country_name'], 'a') === 0);
            } else {
                $this->assertTrue(stripos($result[0]['country_name'], 'z') === 0);
            }
        }

//        $this->addToLog('ORDER BY ALIAS (FUNCTION)');
//        foreach (['asc', 'desc'] as $direction) {
//            $this->orderByData = [['count(city.id)', $direction]];
//            $container = $this->getContainer([]);
//            $ormQb = $this->getOrmQueryBuilder();
//            $ormQb->select('country.name AS country_name, count(city.id) cnt');
//            $ormQb->addGroupBy('country.id');
//            $qb = $this->buildOrmQuery($ormQb, $container);
//            /** @var Country[] $result */
//            $result = $qb->getResult(1, 0);
//            if ($this->debug > 2) {
//                print_r($result);
//            }
//            if ('asc' === $direction) {
//                $this->assertTrue((int) $result[0]['cnt'] === 0);
//            } else {
//                $this->assertTrue((int) $result[0]['cnt'] > 300);
//            }
//        }
    }

    /**
     * runTestDBALQueryBuilder
     */
    protected function runTestDBALQueryBuilder()
    {
        $this->addToLog('BASE WHERE DATA');
        $container = $this->getContainer($this->baseWhereData);
        $dbalQb = $this->getDbalQueryBuilder();
        $dbalQb
            ->select('country.name, capital.name AS capital_name, COUNT(city.id) AS count_cities')
            ->groupBy('country.id, capital.name');

        $qb = $this->buildDbalQuery($dbalQb, $container);

        $result = $qb->getResult(4, 10);

        if ($this->debug > 2) {
            print_r($result);
        }

        $this->assertTrue($qb->totalResultCount() > 0);
        $this->assertTrue(count($result) > 0);
        $this->assertTrue($qb->currentResultCount() === 4);
        $this->assertStringContainsStringIgnoringCase('SELECT', $dbalQb->getSQL());

        $this->addToLog('IS NULL :: IS NOT NULL');
        $this->orderByData = [];
        foreach (['isNull', 'isNotNull'] as $type) {
            $container = $this->getContainer($this->getSingleCondition('capital.id', $type));
            $dbalQb = $this->getDbalQueryBuilder();
            $dbalQb->select('country.name AS country_name, capital.name AS capital_name');
            $qb = $this->buildDbalQuery($dbalQb, $container);
            $result = $qb->getResult(2, 0);
            if ($this->debug > 2) {
                print_r($result);
            }
            if ('isNotNull' === $type) {
                $this->assertTrue(null !== $result[0]['capital_name']);
                $this->assertTrue(null !== $result[1]['capital_name']);
            } else {
                $this->assertTrue(null === $result[0]['capital_name']);
                $this->assertTrue(null === $result[1]['capital_name']);
            }
        }

        $this->addToLog('ORDER BY ALIAS');
        foreach (['asc', 'desc'] as $direction) {
            $this->orderByData = [['country_name', $direction]];
            $container = $this->getContainer([]);
            $dbalQb = $this->getDbalQueryBuilder();
            $dbalQb->select(['country.name AS country_name, region.name region_name', 'continent.name continent_name']);
            $dbalQb->addSelect(['country.id']);
            $qb = $this->buildDbalQuery($dbalQb, $container);
            /** @var Country[] $result */
            $result = $qb->getResult(2, 0);
            if ($this->debug > 2) {
                print_r($result);
            }
            if ('asc' === $direction) {
                $this->assertTrue(stripos($result[0]['country_name'], 'a') === 0);
            } else {
                $this->assertTrue(stripos($result[0]['country_name'], 'z') === 0);
            }
        }

        $this->addToLog('ORDER BY ALIAS (FUNCTION)');
        foreach (['asc', 'desc'] as $direction) {
            $this->orderByData = [['count(city.id)', $direction]];
            $container = $this->getContainer([]);
            $dbalQb = $this->getDbalQueryBuilder();
            $dbalQb->select('country.name AS country_name, count(city.id) cnt');
            $dbalQb->addGroupBy('country.id');
            $qb = $this->buildDbalQuery($dbalQb, $container);
            /** @var Country[] $result */
            $result = $qb->getResult(1, 0);
            if ($this->debug > 2) {
                print_r($result);
            }
            if ('asc' === $direction) {
                $this->assertTrue((int) $result[0]['cnt'] === 0);
            } else {
                $this->assertTrue((int) $result[0]['cnt'] > 300);
            }
        }
    }

    /**
     * @param ORMBuilder         $ormQb
     * @param ContainerInterface $container
     *
     * @return QueryBuilderInterface
     *
     * @throws \Zk2\SpsComponent\QueryBuilderException
     */
    protected function buildOrmQuery(ORMBuilder $ormQb, ContainerInterface $container)
    {
        $qb = QueryBuilderFactory::createQueryBuilder($ormQb);
        $qb
            ->buildWhere($container)
            ->buildOrderBy($this->orderByData);

        if ($this->debug) {
            if ($this->debug > 1) {
                $parameters = array_map(
                    function (Parameter $parameter) {
                        return [
                            'name'  => $parameter->getName(),
                            'type'  => $parameter->getType(),
                            'value' => $parameter->getValue(),
                        ];
                    },
                    $ormQb->getParameters()->toArray()
                );
                print_r($parameters);
            }
            printf("\n%s\n", $ormQb->getDQL());
            printf("\n%s\n%s\n", $ormQb->getQuery()->getSQL(), str_repeat('=', 100));
        }

        return $qb;
    }

    /**
     * @param DBALBuilder        $dbalQb
     * @param ContainerInterface $container
     *
     * @return QueryBuilderInterface
     *
     * @throws \Zk2\SpsComponent\QueryBuilderException
     */
    protected function buildDbalQuery(DBALBuilder $dbalQb, ContainerInterface $container)
    {
        $qb = QueryBuilderFactory::createQueryBuilder($dbalQb);
        $qb
            ->buildWhere($container)
            ->buildOrderBy($this->orderByData);

        if ($this->debug) {
            if ($this->debug > 1) {
                print_r($dbalQb->getParameters());
            }
            printf("\n%s\n%s\n", $dbalQb->getSQL(), str_repeat('=', 100));
        }

        return $qb;
    }

    /**
     * @return ORMBuilder
     */
    protected function getOrmQueryBuilder()
    {
        $qb = new ORMBuilder($this->em);
        $qb
            ->from(Country::class, 'country')
            ->leftJoin('country.continent', 'continent')
            ->leftJoin('country.region', 'region')
            ->leftJoin('country.capital', 'capital')
            ->leftJoin('country.cities', 'city');

        return $qb;
    }

    /**
     * @return DBALBuilder
     */
    protected function getDbalQueryBuilder()
    {
        $qb = new DBALBuilder($this->em->getConnection());
        $qb
            ->from('country', 'country')
            ->leftJoin('country', 'continent', 'continent', 'country.continent_id = continent.id')
            ->leftJoin('country', 'region', 'region', 'country.region_id = region.id')
            ->leftJoin('country', 'city', 'capital', 'country.capital_city_id = capital.id')
            ->leftJoin('country', 'city', 'city', 'city.country_id = country.id');

        return $qb;
    }

    /**
     * @param string $fileName
     *
     * @throws \Exception
     */
    protected function initLogger($fileName)
    {
        $handler = new StreamHandler(__DIR__.'/logs/'.$fileName.'.log', Logger::DEBUG);
        $this->logger = new MonologSQLLogger(null, $handler);
        $this->em->getConnection()->getConfiguration()->setSQLLogger($this->logger);
    }

    /**
     * @param string     $message
     * @param array|null $params
     */
    protected function addToLog($message, array $params = null)
    {
        $this->logger->startQuery(sprintf('%s %s %s', str_repeat('#', 30), $message, str_repeat('#', 30)), $params);
    }

    /**
     * @param string      $property
     * @param string|null $comparisonOperator
     * @param mixed       $value
     * @param array       $function
     *
     * @return array
     */
    protected function getSingleCondition($property, $comparisonOperator = null, $value = null, $function = [])
    {
        return [
            'andOrOperator' => null,
            'collection'    => [
                [
                    'andOrOperator' => null,
                    'condition'     => [
                        'property'           => $property,
                        'comparisonOperator' => $comparisonOperator,
                        'value'              => $value,
                        'sql_function'           => $function,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param Country[] $countries
     *
     * @return array
     */
    protected function getCountriesAsArray(array $countries)
    {
        $array = array_map(
            function (Country $country) {
                return $country->toArray();
            },
            $countries
        );

        return $array;
    }

    /**
     * @param array $data
     *
     * @return ContainerInterface
     */
    abstract protected function getContainer(array $data);

    /**
     * loadData
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\Tools\ToolsException
     * @throws \Exception
     */
    private function loadData()
    {
        if (file_exists(__DIR__.'/fixtures/data.php')) {
            $platform = $this->em->getConnection()->getDatabasePlatform()->getName();
            $tool = new SchemaTool($this->em);
            $classes = array(
                $this->em->getClassMetadata(Region::class),
                $this->em->getClassMetadata(Continent::class),
                $this->em->getClassMetadata(Country::class),
                $this->em->getClassMetadata(CountryLanguage::class),
                $this->em->getClassMetadata(City::class),
            );
            $tool->dropSchema($classes);
            $tool->createSchema($classes);

            if ('mysql' === $platform) {
                $this->em->getConnection()->exec(
                    /** @lang text */'ALTER TABLE country ADD FULLTEXT INDEX country_fullindex_idx (name ASC, local_name ASC, government_form ASC)'
                );
            }

            $regions = $continents = $countries = $countryLanguages = $cities = [];
            require __DIR__.'/fixtures/data.php';

            foreach ($regions as $regionData) {
                $region = new Region();
                $region->setName($regionData['name']);
                $this->em->persist($region);
            }
            $this->em->flush();

            foreach ($continents as $continentData) {
                $continent = new Continent();
                $continent->setName($continentData['name']);
                $this->em->persist($continent);
            }
            $this->em->flush();

            foreach ($countries as $num => $countryData) {
                $country = new Country();
                /** @var Continent $continent */
                $continent = $this->em->getReference(Continent::class, $countryData['continent_id']);
                /** @var Region $region */
                $region = $this->em->getReference(Region::class, $countryData['region_id']);
                $country
                    ->setName($countryData['name'])
                    ->setCode($countryData['code'].' '.$countryData['capital_city_id'])
                    ->setGovernmentForm($countryData['government_form'])
                    ->setLastDate(new \DateTime($countryData['last_date']))
                    ->setLifeExpectancy($countryData['life_expectancy'])
                    ->setLocalName($countryData['local_name'])
                    ->setPopulation($countryData['population'])
                    ->setSurfaceArea($countryData['surface_area'])
                    ->setContinent($continent)
                    ->setRegion($region)
                    ->setIsGreen((bool) !($num & 1));
                $this->em->persist($country);
            }
            $this->em->flush();
            if ('postgresql' === $platform) {
                $this->em->getConnection()->exec(
                    /** @lang text */
                    'UPDATE country SET fts = setweight(to_tsvector(coalesce(name,\'\')), \'A\')
                      || setweight(to_tsvector(coalesce(local_name,\'\')), \'B\')
                      || setweight(to_tsvector(coalesce(government_form,\'\')), \'C\');'
                );
            }

            foreach ($cities as $cityData) {
                $city = new City();
                /** @var Country $country */
                $country = $this->em->getReference(Country::class, $cityData['country_id']);
                $city
                    ->setName($cityData['name'])
                    ->setPopulation($cityData['population'])
                    ->setDistrict($cityData['district'])
                    ->setLastDate(new \DateTime($cityData['last_date']))
                    ->setCountry($country);
                $this->em->persist($city);
            }
            $this->em->flush();

            foreach ($countryLanguages as $countryLanguagesData) {
                $countryLanguage = new CountryLanguage();
                /** @var Country $country */
                $country = $this->em->getReference(Country::class, $countryLanguagesData['country_id']);
                $countryLanguage
                    ->setLang($countryLanguagesData['lang'])
                    ->setIsOfficial($countryLanguagesData['is_official'])
                    ->setPercentage($countryLanguagesData['percentage'])
                    ->setCountry($country);
                $this->em->persist($countryLanguage);
            }
            $this->em->flush();

            $countries = $this->em->getRepository(Country::class)->findAll();
            /** @var Country $country */
            foreach ($countries as $country) {
                $arr = explode(' ', $country->getCode());
                if (!isset($arr[1]) or !$arr[1]) {
                    continue;
                }
                /** @var City $city */
                $city = $this->em->getReference(City::class, $arr[1]);
                $country
                    ->setCapital($city)
                    ->setCode(trim($arr[0]));
            }
            $this->em->flush();
        }
    }
}
