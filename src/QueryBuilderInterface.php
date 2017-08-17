<?php
/**
 * This file is part of the SpsComponent package.
 *
 * (c) Evgeniy Budanov <budanov.ua@gmail.comm> 2017.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zk2\SpsComponent;

use Zk2\SpsComponent\Condition\ContainerInterface;

/**
 * Interface QueryBuilderInterface
 */
interface QueryBuilderInterface
{
    //const AGGREGATE_FUNCTIONS = ['COUNT', 'SUM', 'MAX', 'MIN', 'AVG'];

    /**
     * @return DBALQueryBuilder|ORMQueryBuilder
     */
    public function getQueryBuilder();

    /**
     * @param ContainerInterface $container
     *
     * @return self
     */
    public function buildWhere(ContainerInterface $container);

    /**
     * @param array $fields
     *
     * @return QueryBuilderInterface
     */
    public function buildOrderBy(array $fields);

    /**
     * @param int $limit  = 0
     * @param int $offset = 30
     *
     * @return array
     */
    public function getResult($limit = 0, $offset = 0);

    /**
     * @return int
     */
    public function currentResultCount();

    /**
     * @return int
     */
    public function totalResultCount();

    /**
     * @param ORMQueryBuilder|DBALQueryBuilder $qb
     * @param string                           $partName
     *
     * @return array
     */
    public function getSqlPart($qb, $partName);

    /**
     * @return string
     */
    public function getPlatform();

    /**
     * @return bool
     */
    public function isWithoutTotalResultCount();

    /**
     * @param bool $withoutTotalResultCount
     */
    public function setWithoutTotalResultCount($withoutTotalResultCount);

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return static
     */
    public function setHint($name, $value);
}
