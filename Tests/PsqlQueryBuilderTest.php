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
        if (!getenv('PgSql_host')) {
            $this->markTestSkipped('PgSql :: Skipped');
        }
        $this->baseWhereData['collection'][] = [
            'andOrOperator' => 'OR',
            'condition'     => [
                'property'           => 'country.name',
                'comparisonOperator' => '',
                'value'              => 'Albania',
                'sql_function'           => [
                    'aggregate'  => false,
                    'definition' => 'FULL_TEXT_SEARCH({property}, {value}, \'english\') = TRUE',
                ],
            ],
        ];

        $this->preSetUp('pdo_pgsql');

        parent::setUp();

        $this->em->getConfiguration()->addCustomStringFunction('FULL_TEXT_SEARCH', FullTextSearchFunction::class);
    }

    /**
     * testOrmObjectQueryBuilder
     */
    public function testOrmObjectQueryBuilder()
    {
        $this->initLogger('psql_orm_object');

        $this->runTestOrmObjectQueryBuilder();

        $this->addToLog('SORTABLE NULLS WALKER');
        foreach ([SortableNullsWalker::NULLS_LAST, SortableNullsWalker::NULLS_FIRST] as $type) {
            foreach (['asc', 'desc'] as $direction) {
                $this->orderByData = [['city.id', $direction]];
                $container = $this->getContainer([]);
                $ormQb = $this->getOrmQueryBuilder();
                $ormQb->select('country, capital');
                $qb = $this->buildOrmQuery($ormQb, $container);
                $qb->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SortableNullsWalker::class);
                $qb->setHint('SortableNullsWalker.fields', ['city.id' => $type]);
                /** @var Country[] $result */
                $result = $qb->getResult(2, 0);
                if ($this->debug > 2) {
                    print_r($this->getCountriesAsArray($result));
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
    }

    /**
     * testOrmArrayQueryBuilder
     */
    public function testOrmArrayQueryBuilder()
    {
        $this->initLogger('psql_orm_array');

        $this->runTestOrmArrayQueryBuilder();

        $this->addToLog('SORTABLE NULLS WALKER');
        foreach ([SortableNullsWalker::NULLS_LAST, SortableNullsWalker::NULLS_FIRST] as $type) {
            foreach (['asc', 'desc'] as $direction) {
                $this->orderByData = [['city.id', $direction]];
                $container = $this->getContainer([]);
                $ormQb = $this->getOrmQueryBuilder();
                $ormQb->select('country.name AS country_name, capital.name AS capital_name');
                $qb = $this->buildOrmQuery($ormQb, $container);
                $qb->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SortableNullsWalker::class);
                $qb->setHint('SortableNullsWalker.fields', ['city.id' => $type]);
                /** @var Country[] $result */
                $result = $qb->getResult(2, 0);
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
    }

    /**
     * testDBALQueryBuilder
     */
    public function testDBALQueryBuilder()
    {
        $this->baseWhereData['collection'][2]['condition']['sql_function']['definition'] =
            '{property} @@ to_tsquery( \'english\', {value}) = TRUE';

        $this->initLogger('psql_dbal');

        $this->runTestDBALQueryBuilder();

        $this->addToLog('SORTABLE NULLS WALKER');
        foreach ([SortableNullsWalker::NULLS_LAST, SortableNullsWalker::NULLS_FIRST] as $type) {
            foreach (['asc', 'desc'] as $direction) {
                $this->orderByData = [['city.id', $direction.' '.$type]];
                $container = $this->getContainer([]);
                $dbalQb = $this->getDbalQueryBuilder();
                $dbalQb->select('country.name AS country_name, capital.name AS capital_name');
                $qb = $this->buildDbalQuery($dbalQb, $container);
                $result = $qb->getResult(2, 0);
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
