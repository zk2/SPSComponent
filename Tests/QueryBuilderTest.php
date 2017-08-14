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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\DBAL\Query\QueryBuilder as DBALBuilder;
use Doctrine\ORM\QueryBuilder as ORMBuilder;
use PHPUnit\Framework\TestCase;
use Tests\Entity\City;
use Tests\Entity\Continent;
use Tests\Entity\Country;
use Tests\Entity\CountryLanguage;
use Tests\Entity\Region;
use Zk2\SpsComponent\QueryBuilderFactory;
use Zk2\SpsComponent\Condition\Container;
use Zk2\SpsComponent\Condition\ContainerInterface;

/**
 * Class QueryBuilderTest
 */
class QueryBuilderTest extends TestCase
{
    /**
     * @var int
     */
    protected $limit = 0;

    /**
     * @var int
     */
    protected $offset = 0;

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
    protected $whereData = [
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
                                ],
                            ],
                            [
                                'andOrOperator' => 'OR',
                                'condition' => [
                                    'property' => 'city.name',
                                    'comparisonOperator' => 'in',
                                    'value' => ['boston', 'new york', 'dallas'],
                                    'function' => 'lower',
                                ],
                            ],
                            [
                                'andOrOperator' => 'OR',
                                'condition' => [
                                    'property' => 'city.id',
                                    'comparisonOperator' => 'greaterThan',
                                    'value' => 100,
                                    'function' => 'count',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'andOrOperator' => 'OR',
                'condition' => [
                    'property' => 'country.name',
                    'comparisonOperator' => 'matches',
                    'value' => 'Albania',
                ],
            ],
        ],
    ];


    /**
     * setUp
     */
    protected function setUp()
    {
        $this->limit = 4;
        $this->offset = 20;
    }

    /**
     * @return array
     */
    public function testBuildContainerAndEntityManager()
    {
        $config = Setup::createAnnotationMetadataConfiguration([__DIR__.'/Entity'], true, null, null, false);
        $dbParams = ['driver' => 'pdo_sqlite', 'memory' => true];
        /** @var EntityManagerInterface $entityManager */
        $entityManager = EntityManager::create($dbParams, $config);
        $this->assertInstanceOf(EntityManager::class, $entityManager);
        $this->loadData($entityManager);

        $container = Container::create($this->whereData, $entityManager->getConnection()->getDatabasePlatform()->getName());
        $this->assertInstanceOf(ContainerInterface::class, $container);

        return [$container, $entityManager];
    }

    /**
     * @depends testBuildContainerAndEntityManager
     *
     * @param array $data
     */
    public function testORMQueryBuilder(array $data)
    {
        $container = $data[0];
        $entityManager = $data[1];

        $ormQb = new ORMBuilder($entityManager);
        $ormQb
            ->select('country, continent, region, capital, city')
            ->from(Country::class, 'country', 'country.id')
            ->leftJoin('country.continent', 'continent')
            ->leftJoin('country.region', 'region')
            ->leftJoin('country.capital', 'capital')
            ->leftJoin('country.cities', 'city');

        $qb = QueryBuilderFactory::createQueryBuilder($ormQb);
        $qb
            ->buildWhere($container)
            ->buildOrderBy($this->orderByData)
        ;

        //print_r($ormQb->getParameters());
        $result = array_map(
            function (Country $country) {
                return $country->toArray();
            },
            $qb->getResult($this->limit, $this->offset)
        );
        //print_r($result);
        //printf("\n%s\n", $ormQb->getDQL());
        //printf("\n%s\n", $ormQb->getQuery()->getSQL());
        $this->assertTrue($qb->totalResultCount() > 0);
        $this->assertTrue(count($result) > 0);
        $this->assertTrue($qb->currentResultCount() === $this->limit);
        $this->assertContains('SELECT', $ormQb->getDQL());
        $this->assertContains('SELECT', $ormQb->getQuery()->getSQL());

        $ormQb = new ORMBuilder($entityManager);
        $ormQb
            ->select(
                'country.name AS country_name, continent.name AS continent_name, region.name AS region_name,
                 capital.name AS capital_name, COUNT(city.id) AS cnt'
            )
            ->from(Country::class, 'country', 'country.id')
            ->leftJoin('country.continent', 'continent')
            ->leftJoin('country.region', 'region')
            ->leftJoin('country.capital', 'capital')
            ->leftJoin('country.cities', 'city')
            ->groupBy('country.id');

        $qb = QueryBuilderFactory::createQueryBuilder($ormQb);
        $qb
            ->buildWhere($container)
            ->buildOrderBy($this->orderByData)
        ;

        //print_r($ormQb->getParameters());
        $result = $qb->getResult($this->limit, $this->offset);

        $this->assertTrue($qb->totalResultCount() > 0);
        $this->assertTrue(count($result) > 0);
        $this->assertTrue($qb->currentResultCount() === $this->limit);
        $this->assertContains('SELECT', $ormQb->getDQL());
        $this->assertContains('SELECT', $ormQb->getQuery()->getSQL());
        //print_r($result);
        //printf("\n%s\n", $ormQb->getDQL());
        //printf("\n%s\n", $ormQb->getQuery()->getSQL());
    }

    /**
     * @depends testBuildContainerAndEntityManager
     *
     * @param array $data
     */
    public function testDBALQueryBuilder(array $data)
    {
        $container = $data[0];
        $entityManager = $data[1];

        $dbalQb = new DBALBuilder($entityManager->getConnection());
        $dbalQb
            ->select('country.name, capital.name AS capital_name, COUNT(city.id) AS count_cities')
            ->from('country', 'country')
            ->leftJoin('country', 'continent', 'continent', 'country.continent_id = continent.id')
            ->leftJoin('country', 'region', 'region', 'country.region_id = region.id')
            ->leftJoin('country', 'city', 'capital', 'country.capital_city_id = capital.id')
            ->leftJoin('country', 'city', 'city', 'city.country_id = country.id')
            ->groupBy('country.id, capital.name')
        ;

        $qb = QueryBuilderFactory::createQueryBuilder($dbalQb);
        $qb
            ->buildWhere($container)
            ->buildOrderBy($this->orderByData)
        ;

        //print_r($dbalQb->getParameters());
        $result = $qb->getResult($this->limit, $this->offset);

        $this->assertTrue($qb->totalResultCount() > 0);
        $this->assertTrue(count($result) > 0);
        $this->assertTrue($qb->currentResultCount() === $this->limit);
        $this->assertContains('SELECT', $dbalQb->getSQL());
        //print_r($result);
        //printf("\n%s\n", $dbalQb->getSQL());
    }

    /**
     * @param EntityManagerInterface $em
     */
    private function loadData(EntityManagerInterface $em)
    {
        if (file_exists(__DIR__.'/fixtures/data.php')) {
            $tool = new SchemaTool($em);
            $classes = array(
                $em->getClassMetadata('Tests\Entity\Region'),
                $em->getClassMetadata('Tests\Entity\Continent'),
                $em->getClassMetadata('Tests\Entity\Country'),
                $em->getClassMetadata('Tests\Entity\CountryLanguage'),
                $em->getClassMetadata('Tests\Entity\City'),
            );
            $tool->createSchema($classes);

            $regions = $continents = $countries = $countryLanguages = $cities = [];
            require __DIR__.'/fixtures/data.php';

            foreach ($regions as $regionData) {
                $region = new Region();
                $region->setName($regionData['name']);
                $em->persist($region);
            }
            $em->flush();

            foreach ($continents as $continentData) {
                $continent = new Continent();
                $continent->setName($continentData['name']);
                $em->persist($continent);
            }
            $em->flush();

            foreach ($countries as $countryData) {
                $country = new Country();
                /** @var Continent $continent */
                $continent = $em->getReference(Continent::class, $countryData['continent_id']);
                /** @var Region $region */
                $region = $em->getReference(Region::class, $countryData['region_id']);
                $country
                    ->setName($countryData['name'])
                    ->setCode($countryData['code'])
                    ->setCode2($countryData['code2'])
                    ->setGnp($countryData['gnp'])
                    ->setGnpOld($countryData['gnp_old'])
                    ->setGovernmentForm($countryData['government_form'])
                    ->setHeadOfState($countryData['head_of_state'])
                    ->setIndepYear($countryData['indep_year'])
                    ->setLastDate(new \DateTime($countryData['last_date']))
                    ->setLifeExpectancy($countryData['life_expectancy'])
                    ->setLocalName($countryData['local_name'])
                    ->setPopulation($countryData['population'])
                    ->setSurfaceArea($countryData['surface_area'])
                    ->setContinent($continent)
                    ->setRegion($region)
                    ->setFlag($countryData['capital_city_id']);
                $em->persist($country);
            }
            $em->flush();

            foreach ($cities as $cityData) {
                $city = new City();
                /** @var Country $country */
                $country = $em->getReference(Country::class, $cityData['country_id']);
                $city
                    ->setName($cityData['name'])
                    ->setPopulation($cityData['population'])
                    ->setDistrict($cityData['district'])
                    ->setLastDate(new \DateTime($cityData['last_date']))
                    ->setCountry($country);
                $em->persist($city);
            }
            $em->flush();

            foreach ($countryLanguages as $countryLanguagesData) {
                $countryLanguage = new CountryLanguage();
                /** @var Country $country */
                $country = $em->getReference(Country::class, $countryLanguagesData['country_id']);
                $countryLanguage
                    ->setLang($countryLanguagesData['lang'])
                    ->setIsOfficial($countryLanguagesData['is_official'])
                    ->setPercentage($countryLanguagesData['percentage'])
                    ->setCountry($country);
                $em->persist($countryLanguage);
            }
            $em->flush();

            $countries = $em->getRepository(Country::class)->findAll();
            /** @var Country $country */
            foreach ($countries as $country) {
                if (!$country->getFlag()) {
                    continue;
                }
                /** @var City $city */
                $city = $em->getReference(City::class, $country->getFlag());
                $country
                    ->setCapital($city)
                    ->setFlag(null);
            }
            $em->flush();
        }
    }
}
