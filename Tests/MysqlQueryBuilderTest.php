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

use Doctrine\ORM\Query;
use Tests\Doctrine\FullTextSearchFunction;
use Tests\Doctrine\SortableNullsWalker;
use Tests\Entity\City;
use Tests\Entity\Country;
use Zk2\SpsComponent\Condition\Container;
use Zk2\SpsComponent\Condition\ContainerInterface;

/**
 * Class MysqlQueryBuilderTest
 */
class MysqlQueryBuilderTest extends AbstractQueryBuilderTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!getenv('MySql_host')) {
            $this->markTestSkipped('MySql :: Skipped');
        }
        $this->baseWhereData['collection'][] = [
            'andOrOperator' => 'OR',
            'condition' => [
                'property' => 'country.name,country.localName,country.governmentForm',
                'comparisonOperator' => '',
                'value' => 'Albania',
                'function' => [
                    'aggregate' => false,
                    'definition' => 'FULL_TEXT_SEARCH({property}, {value} \'IN NATURAL MODE\', 1) != 0',
                ],
            ],
        ];

        $this->dbParams = [
            'driver' => 'pdo_mysql',
            'host' => getenv('MySql_host'),
            'port' => getenv('MySql_port'),
            'user' => getenv('MySql_username'),
            'password' => getenv('MySql_password'),
            'dbname' => getenv('MySql_database'),
        ];

        parent::setUp();

        $this->em->getConfiguration()->addCustomStringFunction('FULL_TEXT_SEARCH', FullTextSearchFunction::class);
    }

    /**
     * testOrmObjectQueryBuilder
     */
    public function testOrmObjectQueryBuilder()
    {
        $this->initLogger('mysql_orm_object');
        $this->addToLog('BASE WHERE DATA');

        parent::testOrmObjectQueryBuilder();

        $this->addToLog('SORTABLE NULLS WALKER');
        $this->orderByData = [['city.id', 'asc']];
        $this->limit = 2;
        $this->offset = 0;
        foreach ([SortableNullsWalker::NULLS_LAST, SortableNullsWalker::NULLS_FIRST] as $type) {
            $container = $this->getContainer([]);
            $ormQb = $this->getOrmQueryBuilder();
            $ormQb->select('country, capital');
            $qb = $this->buildOrmQuery($ormQb, $container);
            $qb->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SortableNullsWalker::class);
            $qb->setHint('SortableNullsWalker.fields', ['city.id' => $type]);
            /** @var Country[] $result */
            $result = $qb->getResult($this->limit, $this->offset);

            if ($this->debug > 2) {
                $resultArray = array_map(
                    function (Country $country) {
                        return $country->toArray();
                    },
                    $result
                );
                print_r($resultArray);
            }

            if (SortableNullsWalker::NULLS_LAST === $type) {
                $this->assertInstanceOf(City::class, $result[0]->getCapital());
                $this->assertInstanceOf(City::class, $result[1]->getCapital());
            } else {
                $this->assertTrue(null === $result[0]->getCapital());
                $this->assertTrue(null === $result[1]->getCapital());
            }
        }
    }

    /**
     * testOrmArrayQueryBuilder
     */
    public function testOrmArrayQueryBuilder()
    {
        $this->initLogger('mysql_orm_array');
        $this->addToLog('BASE WHERE DATA');

        parent::testOrmArrayQueryBuilder();

        $this->addToLog('SORTABLE NULLS WALKER');
        $this->orderByData = [['city.id', 'asc']];
        $this->limit = 2;
        $this->offset = 0;
        foreach ([SortableNullsWalker::NULLS_LAST, SortableNullsWalker::NULLS_FIRST] as $type) {
            $container = $this->getContainer([]);
            $ormQb = $this->getOrmQueryBuilder();
            $ormQb->select('country.name AS country_name, city.name AS capital_name');
            $qb = $this->buildOrmQuery($ormQb, $container);
            $qb->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SortableNullsWalker::class);
            $qb->setHint('SortableNullsWalker.fields', ['city.id' => $type]);
            /** @var Country[] $result */
            $result = $qb->getResult($this->limit, $this->offset);

            if ($this->debug > 2) {
                print_r($result);
            }

            if (SortableNullsWalker::NULLS_LAST === $type) {
                $this->assertTrue(null !== $result[0]['capital_name']);
                $this->assertTrue(null !== $result[1]['capital_name']);
            } else {
                $this->assertTrue(null === $result[0]['capital_name']);
                $this->assertTrue(null === $result[1]['capital_name']);
            }
        }
    }

    /**
     * testDBALQueryBuilder
     */
    public function testDBALQueryBuilder()
    {
        $this->baseWhereData['collection'][2]['condition']['function']['definition'] =
            'MATCH ({property}) AGAINST ({value}) != 0';
        $this->baseWhereData['collection'][2]['condition']['property'] =
            'country.name,country.local_name,country.government_form';

        $this->initLogger('mysql_dbal');
        $this->addToLog('BASE WHERE DATA');

        parent::testDBALQueryBuilder();

        $this->addToLog('SORTABLE NULLS WALKER');
        $this->limit = 2;
        $this->offset = 0;
        foreach ([SortableNullsWalker::NULLS_LAST, SortableNullsWalker::NULLS_FIRST] as $type) {
            $sortExpr = SortableNullsWalker::NULLS_LAST === $type ? 'ISNULL(city.id), city.id' : 'city.id';
            $this->orderByData = [[$sortExpr, 'asc']];
            $container = $this->getContainer([]);
            $dbalQb = $this->getDbalQueryBuilder();
            $dbalQb->select('country.name AS country_name, capital.name AS capital_name');
            $qb = $this->buildDbalQuery($dbalQb, $container);
            $result = $qb->getResult($this->limit, $this->offset);

            if ($this->debug > 2) {
                print_r($result);
            }

            if (SortableNullsWalker::NULLS_LAST === $type) {
                $this->assertTrue(null !== $result[0]['capital_name']);
                $this->assertTrue(null !== $result[1]['capital_name']);
            } else {
                $this->assertTrue(null === $result[0]['capital_name']);
                $this->assertTrue(null === $result[1]['capital_name']);
            }
        }
    }

    /**
     * @param array $data
     *
     * @return ContainerInterface
     */
    protected function getContainer(array $data)
    {
        $container = Container::create($data);
        $this->assertInstanceOf(ContainerInterface::class, $container);

        return $container;
    }
}
