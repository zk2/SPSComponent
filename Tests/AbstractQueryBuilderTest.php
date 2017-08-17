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

use Cobaia\Doctrine\MonologSQLLogger;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\DBAL\Query\QueryBuilder as DBALBuilder;
use Doctrine\ORM\QueryBuilder as ORMBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Tests\Entity\City;
use Tests\Entity\Continent;
use Tests\Entity\Country;
use Tests\Entity\CountryLanguage;
use Tests\Entity\Region;
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
    protected $dbParams = [];

    /**
     * @var int
     */
    protected $limit = 4;

    /**
     * @var int
     */
    protected $offset = 10;

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
        //['country.name', 'asc'],
        ['property' => 'country.population', 'direction' => 'asc'],
    ];

    /**
     * @var array
     */
    protected $baseWhereData = [
        'andOrOperator' => null,
        'collection' => [
            [
                'andOrOperator' => null,
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
                                    'property' => 'city.name',
                                    'comparisonOperator' => 'endsWith',
                                    'value' => 'on',
                                    'function' => [
                                        'aggregate' => false,
                                        'definition' => 'lower({property})',
                                    ],
                                ],
                            ],
                            [
                                'andOrOperator' => 'OR',
                                'condition' => [
                                    'property' => 'city.name',
                                    'comparisonOperator' => 'in',
                                    'value' => ['boston', 'new york', 'dallas'],
                                    'function' => [
                                        'aggregate' => false,
                                        'definition' => 'lower({property})',
                                    ],
                                ],
                            ],
                            [
                                'andOrOperator' => 'OR',
                                'condition' => [
                                    'property' => 'city.id',
                                    'comparisonOperator' => 'greaterThan',
                                    'value' => 100,
                                    'function' => [
                                        'aggregate' => true,
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
    protected function setUp()
    {
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
            $this->loadData();
        } catch (\Exception $e) {
            $this->markTestSkipped('Skipped'.PHP_EOL.$e->getMessage());
        }
    }

    /**
     * testOrmObjectQueryBuilder
     */
    public function testOrmObjectQueryBuilder()
    {
        $container = $this->getContainer($this->baseWhereData);
        $ormQb = $this->getOrmQueryBuilder();
        $ormQb->select('country, continent, region, capital, city');

        $qb = $this->buildOrmQuery($ormQb, $container);

        $result = array_map(
            function (Country $country) {
                return $country->toArray();
            },
            $qb->getResult($this->limit, $this->offset)
        );

        if ($this->debug > 2) {
            print_r($result);
        }

        $this->assertTrue($qb->totalResultCount() > 0);
        $this->assertTrue(count($result) > 0);
        $this->assertTrue($qb->currentResultCount() === $this->limit);
        $this->assertContains('SELECT', $ormQb->getDQL());
        $this->assertContains('SELECT', $ormQb->getQuery()->getSQL());
    }

    /**
     * testOrmArrayQueryBuilder
     */
    public function testOrmArrayQueryBuilder()
    {
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

        $result = $qb->getResult($this->limit, $this->offset);

        if ($this->debug > 2) {
            print_r($result);
        }

        $this->assertTrue($qb->totalResultCount() > 0);
        $this->assertTrue(count($result) > 0);
        $this->assertTrue($qb->currentResultCount() === $this->limit);
        $this->assertContains('SELECT', $ormQb->getDQL());
        $this->assertContains('SELECT', $ormQb->getQuery()->getSQL());
    }

    /**
     * testDBALQueryBuilder
     */
    public function testDBALQueryBuilder()
    {
        $container = $this->getContainer($this->baseWhereData);
        $dbalQb = $this->getDbalQueryBuilder();
        $dbalQb
            ->select('country.name, capital.name AS capital_name, COUNT(city.id) AS count_cities')
            ->groupBy('country.id, capital.name');

        $qb = $this->buildDbalQuery($dbalQb, $container);

        $result = $qb->getResult($this->limit, $this->offset);

        if ($this->debug > 2) {
            print_r($result);
        }

        $this->assertTrue($qb->totalResultCount() > 0);
        $this->assertTrue(count($result) > 0);
        $this->assertTrue($qb->currentResultCount() === $this->limit);
        $this->assertContains('SELECT', $dbalQb->getSQL());
    }

    /**
     * @param array $data
     *
     * @return ContainerInterface
     */
    abstract protected function getContainer(array $data);

    /**
     * @param ORMBuilder         $ormQb
     * @param ContainerInterface $container
     *
     * @return QueryBuilderInterface
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
                            'name' => $parameter->getName(),
                            'type' => $parameter->getType(),
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
        //$this->logger->stopQuery();
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
            'collection' => [
                [
                    'andOrOperator' => null,
                    'condition' => [
                        'property' => $property,
                        'comparisonOperator' => $comparisonOperator,
                        'value' => $value,
                        'function' => $function,
                    ],
                ],
            ],
        ];
    }

    /**
     * loadData
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
                    'ALTER TABLE country ADD FULLTEXT INDEX country_fullindex_idx (name ASC, local_name ASC, government_form ASC)'
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

            foreach ($countries as $countryData) {
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
                    ->setRegion($region);
                $this->em->persist($country);
            }
            $this->em->flush();

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
