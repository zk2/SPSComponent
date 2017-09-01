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

use Zk2\SpsComponent\Condition\Container;
use Zk2\SpsComponent\Condition\ContainerInterface;

/**
 * Class SqliteQueryBuilderTest
 */
class SqliteQueryBuilderTest extends AbstractQueryBuilderTest
{
    /**
     * @var array $dbParams
     */
    protected $dbParams = ['driver' => 'pdo_sqlite', 'memory' => true];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * testOrmObjectQueryBuilder
     */
    public function testOrmObjectQueryBuilder()
    {
        $this->initLogger('sqlite_orm_object');
        $this->addToLog('BASE WHERE DATA');

        $this->runTestOrmObjectQueryBuilder();
    }

    /**
     * testOrmArrayQueryBuilder
     */
    public function testOrmArrayQueryBuilder()
    {
        $this->initLogger('sqlite_orm_array');
        $this->addToLog('BASE WHERE DATA');

        $this->runTestOrmArrayQueryBuilder();
    }

    /**
     * testDBALQueryBuilder
     */
    public function testDBALQueryBuilder()
    {
        $this->initLogger('sqlite_dbal');
        $this->addToLog('BASE WHERE DATA');

        $this->runTestDBALQueryBuilder();
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
