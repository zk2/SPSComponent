<?php

namespace Zk2\SpsComponent;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;
use Doctrine\ORM\Query\Parameter;

abstract class AbstractQueryBuilder
{
    /**
     * @var ORMQueryBuilder|DBALQueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var string
     */
    protected $platform;

    /**
     * @var int
     */
    protected $totalResultCount = 0;

    /**
     * @var array
     */
    protected $result = [];

    /**
     * @var ArrayCollection|Parameter[]|array
     */
    protected $parameters;

    /**
     * @var string
     */
    protected $condition = '';

    /**
     * @var integer
     */
    protected $aggNumber = 0;

    /**
     * @var string
     */
    protected $rootEntity;

    /**
     * @var string
     */
    protected $rootAlias;

    /**
     * @var string
     */
    protected $primary;

    /**
     * @var array
     */
    protected $additionalFunctions = [];

    /**
     * @param ORMQueryBuilder|DBALQueryBuilder $queryBuilder
     */
    public function __construct($queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return DBALQueryBuilder|ORMQueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @return int
     */
    public function currentResultCount()
    {
        return count($this->result);
    }

    /**
     * @return int
     */
    public function totalResultCount()
    {
        return $this->totalResultCount ?: $this->currentResultCount();
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function buildOrderBy(array $fields)
    {
        foreach ($fields as $field) {
            if (!is_array($field)) {
                $field = [$field];
            }
            $property = array_shift($field);
            $direction = $field ? array_shift($field) : 'ASC';
            $this->addOrderBy($property, $direction);
        }

        return $this;
    }

    /**
     * @param string $rootEntity
     * @return null|string
     */
    public function getPrimaryKeyName($rootEntity)
    {
        $connection = $this->queryBuilder->getConnection();
        $databasePlatformName = $connection->getDatabasePlatform()->getName();
        $query = null;
        switch ($databasePlatformName) {
            case 'postgresql':
                $query = "SELECT a.attname FROM pg_index i "
                    ."JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey) "
                    ."WHERE  i.indrelid = '".$rootEntity."'::regclass AND i.indisprimary";
                break;
            case 'mysql':
                $query = "SELECT k.column_name FROM information_schema.table_constraints t "
                    ."JOIN information_schema.key_column_usage k "
                    ."USING(constraint_name,table_schema,table_name) "
                    ." WHERE t.constraint_type='PRIMARY KEY' "
                    ."AND t.table_schema='".$connection->getDatabase()."' "
                    ."AND t.table_name='".$rootEntity."';";
                break;
            case 'sqlite':
                $query = "PRAGMA table_info(".$rootEntity.")";
                $stmt = $connection->executeQuery($query);
                while ($row = $stmt->fetch()) {
                    if ($row['pk']) {
                        return $row['name'];
                    }
                }
        }
        if ($query) {
            $stmt = $connection->executeQuery($query);
            return $stmt->fetchColumn();
        }

        return null;
    }

    /**
     * @param ORMQueryBuilder|DBALQueryBuilder $qb
     * @param string $partName
     * @return array
     */
    public function getSqlPart($qb, $partName)
    {
        return $qb instanceof ORMQueryBuilder ? $qb->getDQLPart($partName) : $qb->getQueryPart($partName);
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->getConnection()->getDatabasePlatform()->getName();
    }

    /**
     * @param string $name
     * @param string $class
     * @param string $type
     * @return void
     * @throws QueryBuilderException
     */
    public function addCustomFunction($name, $class, $type)
    {
        if (!class_exists($class)) {
            throw new QueryBuilderException(sprintf('Class "%s" is not exists', $class));
        }
        $configuration = $this->queryBuilder->getEntityManager()->getConfiguration();
        switch (strtolower($type)) {
            case 'string':
                if (null === $configuration->getCustomStringFunction($name)) {
                    $configuration->addCustomStringFunction($name, $class);
                }
                break;
            case 'numeric':
                if (null === $configuration->getCustomNumericFunction($name)) {
                    $configuration->addCustomNumericFunction($name, $class);
                }
                break;
            case 'datetime':
                if (null === $configuration->getCustomDatetimeFunction($name)) {
                    $configuration->addCustomDatetimeFunction($name, $class);
                }
                break;
            default:
                throw new QueryBuilderException(sprintf('Type "%s" is invalid', $type));
        }
    }

    /**
     * @param ORMQueryBuilder|DBALQueryBuilder $qb
     * @param array|null $parts
     * @return ORMQueryBuilder|DBALQueryBuilder
     */
    protected function resetSqlParts($qb, array $parts = null)
    {
        if ($qb instanceof ORMQueryBuilder) {
            $qb->resetDQLParts($parts);
        } elseif ($qb instanceof DBALQueryBuilder) {
            $qb->resetQueryParts($parts);
        }

        return $qb;
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function isAggregateFunction($name)
    {
        return in_array(strtoupper($name), QueryBuilderInterface::AGGREGATE_FUNCTIONS);
    }

    /**
     * @param $condition
     * @return string
     */
    protected function trimAndOr($condition)
    {
        if (strpos($condition, ' AND ') === 0) {
            $condition = substr($condition, 5);
        } elseif (strpos($condition, ' OR ') === 0) {
            $condition = substr($condition, 4);
        }

        return $condition;
    }

    /**
     * @param string $property
     * @param string $direction
     */
    protected function addOrderBy($property, $direction)
    {
        $connection = $this->getConnection();
        $databasePlatformName = $connection->getDatabasePlatform()->getName();
        $direction = strtoupper($direction);
        switch ($databasePlatformName) {
            case 'postgresql':
                //$direction = preg_replace(['/asc/i', '/desc/i'], ['ASC NULLS FIRST', 'DESC NULLS LAST'], $direction);
        }
        $this->queryBuilder->addOrderBy($property, $direction);
    }

    /**
     * @return Connection
     */
    protected function getConnection()
    {
        return $this->queryBuilder instanceof ORMQueryBuilder
            ? $this->queryBuilder->getEntityManager()->getConnection()
            : $this->queryBuilder->getConnection();
    }

    /**
     * @return string
     */
    protected function aliasDotPrimary()
    {
        return sprintf("%s.%s", $this->rootAlias, $this->primary);
    }
}