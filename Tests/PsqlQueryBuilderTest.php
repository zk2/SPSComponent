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
 * Class PsqlQueryBuilderTest
 */
class PsqlQueryBuilderTest extends AbstractQueryBuilderTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->baseWhereData['collection'][] = [
            'andOrOperator' => 'OR',
            'condition' => [
                'property' => 'country.name',
                'comparisonOperator' => '',
                'value' => 'Albania',
                'function' => [
                    'aggregate' => false,
                    'definition' => 'FULL_TEXT_SEARCH({property}, {value}, \'english\') = TRUE',
                ],
            ],
        ];

        $this->dbParams = [
            'driver' => 'pdo_pgsql',
            'host' => getenv('PgSql_host'),
            'port' => getenv('PgSql_port'),
            'user' => getenv('PgSql_username'),
            'password' => getenv('PgSql_password'),
            'dbname' => getenv('PgSql_database'),
        ];

        parent::setUp();

        $this->em->getConfiguration()->addCustomStringFunction('FULL_TEXT_SEARCH', FullTextSearchFunction::class);
    }

    /**
     * testOrmObjectQueryBuilder
     */
    public function testOrmObjectQueryBuilder()
    {
        $this->initLogger('psql_orm_object');
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

        $this->addToLog('IS NULL :: IS NOT NULL');
        $this->orderByData = [];
        $this->limit = 2;
        $this->offset = 0;
        $container = $this->getContainer($this->getSingleCondition('capital.id', 'isNull'));
        $ormQb = $this->getOrmQueryBuilder();
        $ormQb->select('country, capital');
        $qb = $this->buildOrmQuery($ormQb, $container);
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
        $this->assertTrue(null === $result[0]->getCapital());
        $this->assertTrue(null === $result[1]->getCapital());

        $container = $this->getContainer($this->getSingleCondition('capital.id', 'isNotNull'));
        $ormQb = $this->getOrmQueryBuilder();
        $ormQb->select('country, capital');
        $qb = $this->buildOrmQuery($ormQb, $container);
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
        $this->assertTrue(null !== $result[0]->getCapital());
        $this->assertTrue(null !== $result[1]->getCapital());
    }

    /**
     * testOrmArrayQueryBuilder
     */
    public function testOrmArrayQueryBuilder()
    {
        $this->initLogger('psql_orm_array');
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
            '{property} @@ to_tsquery( \'english\', {value}) = TRUE';

        $this->initLogger('psql_dbal');
        $this->addToLog('BASE WHERE DATA');

        parent::testDBALQueryBuilder();

        $this->addToLog('SORTABLE NULLS WALKER');
        $this->limit = 2;
        $this->offset = 0;
        foreach ([SortableNullsWalker::NULLS_LAST, SortableNullsWalker::NULLS_FIRST] as $type) {
            $this->orderByData = [['city.id', 'asc '.$type]];
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
