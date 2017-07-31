<?php

namespace Zk2\SpsComponent;

use Zk2\SpsComponent\Condition\ContainerInterface;


/**
 * Interface QueryBuilderInterface
 */
interface QueryBuilderInterface
{
    const AGGREGATE_FUNCTIONS = ['COUNT', 'SUM', 'MAX', 'MIN', 'AVG'];

    /**
     * @return DBALQueryBuilder|ORMQueryBuilder
     */
    public function getQueryBuilder();

    /**
     * @param ContainerInterface $container
     * @return self
     */
    public function buildWhere(ContainerInterface $container);

    /**
     * @param array $fields
     * @return QueryBuilderInterface
     */
    public function buildOrderBy(array $fields);

    /**
     * @param int $offset = 0
     * @param int $limit = 30
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
     * @param string $partName
     * @return array
     */
    public function getSqlPart($qb, $partName);

    /**
     * @return string
     */
    public function getPlatform();

    /**
     * @param string $name
     * @param string $class
     * @param string $type
     * @return void
     */
    public function addCustomFunction($name, $class, $type);

}