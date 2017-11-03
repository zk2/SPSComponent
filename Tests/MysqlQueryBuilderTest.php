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
            'condition'     => [
                'property'           => 'country.name,country.localName,country.governmentForm',
                'comparisonOperator' => '',
                'value'              => 'Albania',
                'function'           => [
                    'aggregate'  => false,
                    'definition' => 'FULL_TEXT_SEARCH({property}, {value} \'IN NATURAL MODE\', 0) != 0',
                ],
            ],
        ];

        $this->preSetUp('pdo_mysql');

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
                    $this->assertInstanceOf(
                        City::class,
                        $result[0]->getCapital(),
                        sprintf('%s -> %s', $type, $direction)
                    );
                    $this->assertInstanceOf(
                        City::class,
                        $result[1]->getCapital(),
                        sprintf('%s -> %s', $type, $direction)
                    );
                } else {
                    $this->assertTrue(null === $result[0]->getCapital(), sprintf('%s -> %s', $type, $direction));
                    $this->assertTrue(null === $result[1]->getCapital(), sprintf('%s -> %s', $type, $direction));
                }
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

        $this->runTestOrmArrayQueryBuilder();

        $this->addToLog('SORTABLE NULLS WALKER');
        foreach ([SortableNullsWalker::NULLS_LAST, SortableNullsWalker::NULLS_FIRST] as $type) {
            foreach (['asc', 'desc'] as $direction) {
                $this->orderByData = [['city.id', $direction]];
                $container = $this->getContainer([]);
                $ormQb = $this->getOrmQueryBuilder();
                $ormQb->select('country.name AS country_name, city.name AS capital_name');
                $qb = $this->buildOrmQuery($ormQb, $container);
                $qb->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SortableNullsWalker::class);
                $qb->setHint('SortableNullsWalker.fields', ['city.id' => $type]);
                /** @var Country[] $result */
                $result = $qb->getResult(2, 0);
                if ($this->debug > 2) {
                    print_r($result);
                }
                if (SortableNullsWalker::NULLS_LAST === $type) {
                    $this->assertTrue(null !== $result[0]['capital_name'], sprintf('%s -> %s', $type, $direction));
                    $this->assertTrue(null !== $result[1]['capital_name'], sprintf('%s -> %s', $type, $direction));
                } else {
                    $this->assertTrue(null === $result[0]['capital_name'], sprintf('%s -> %s', $type, $direction));
                    $this->assertTrue(null === $result[1]['capital_name'], sprintf('%s -> %s', $type, $direction));
                }
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

        $this->runTestDBALQueryBuilder();

        $this->addToLog('SORTABLE NULLS WALKER');
        foreach ([SortableNullsWalker::NULLS_LAST, SortableNullsWalker::NULLS_FIRST] as $type) {
            foreach (['asc', 'desc'] as $direction) {
                $nullDirection = SortableNullsWalker::NULLS_LAST === $type ? 'ASC' : 'DESC';
                $sortExpr = sprintf('ISNULL(city.id) %s, city.id', $nullDirection);
                $this->orderByData = [[$sortExpr, $direction]];
                $container = $this->getContainer([]);
                $dbalQb = $this->getDbalQueryBuilder();
                $dbalQb->select('country.name AS country_name, capital.name AS capital_name');
                $qb = $this->buildDbalQuery($dbalQb, $container);
                $result = $qb->getResult(2, 0);
                if ($this->debug > 2) {
                    print_r($result);
                }
                if (SortableNullsWalker::NULLS_LAST === $type) {
                    $this->assertTrue(null !== $result[0]['capital_name'], sprintf('%s -> %s', $type, $direction));
                    $this->assertTrue(null !== $result[1]['capital_name'], sprintf('%s -> %s', $type, $direction));
                } else {
                    $this->assertTrue(null === $result[0]['capital_name'], sprintf('%s -> %s', $type, $direction));
                    $this->assertTrue(null === $result[1]['capital_name'], sprintf('%s -> %s', $type, $direction));
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
