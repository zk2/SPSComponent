<?php

namespace Zk2\SpsComponent;

use Doctrine\DBAL\Query\QueryBuilder as DBALBuilder;
use Doctrine\ORM\QueryBuilder as ORMBuilder;

class QueryBuilderFactory
{
    /**
     * @param ORMBuilder|DBALBuilder $queryBuilder
     * @return QueryBuilderInterface
     * @throws QueryBuilderException
     */
    public static function createQueryBuilder($queryBuilder)
    {
        if ($queryBuilder instanceof ORMBuilder) {
            return new ORMQueryBuilder($queryBuilder);
        }
        if ($queryBuilder instanceof DBALBuilder) {
            return new DBALQueryBuilder($queryBuilder);
        }

        throw new QueryBuilderException(
            sprintf('Invalid parameter "queryBuilder". Use %s or %s', ORMBuilder::class, DBALBuilder::class)
        );
    }
}