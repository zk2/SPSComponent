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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;
use Zk2\SpsComponent\Condition\ConditionInterface;

/**
 * Class AbstractQueryBuilder
 */
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
     * @var string
     */
    protected $condition = '';

    /**
     * @var int
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
     * @var bool
     */
    protected $withoutTotalResultCount = false;

    /**
     * @var array
     */
    protected $hints = [];

    /**
     * @var array
     */
    protected $aliasMapping = [];

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
     *
     * @return $this
     */
    public function buildOrderBy(array $fields)
    {
        $this->parseSelectPath();
        foreach ($fields as $field) {
            if (!is_array($field)) {
                $field = [$field];
            }
            $property = array_shift($field);
            if (!in_array($property, $this->aliasMapping) and isset($this->aliasMapping[$property])) {
                $property = $this->aliasMapping[$property];
            }
            $direction = $field ? array_shift($field) : 'ASC';
            $function = $field ? array_shift($field) : null;
            if ($function) {
                $property = sprintf("%s(%s)", $function, $property);
            }
            $this->addOrderBy($property, $direction);
        }

        return $this;
    }

    /**
     * @param string $func
     *
     * @return bool
     */
    public function isAggFunc($func)
    {
        return preg_match('/'.implode('\(|', QueryBuilderInterface::AGGREGATE_FUNCTIONS).'\(/i', $func);
    }

    /**
     * @param string $rootEntity
     *
     * @return null|string
     */
    public function getPrimaryKeyName($rootEntity)
    {
        $connection = $this->queryBuilder->getConnection();
        $databasePlatformName = $connection->getDatabasePlatform()->getName();
        $query = null;
        switch ($databasePlatformName) {
            case 'postgresql':
                /** @noinspection SpellCheckingInspection */
                $query = sprintf(
                    "%s %s %s",
                    "SELECT a.attname FROM pg_index i",
                    "JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)",
                    "WHERE  i.indrelid = '".$rootEntity."'::regclass AND i.indisprimary"
                );
                $stmt = $connection->executeQuery($query);

                return $stmt->fetchColumn();
            case 'mysql':
                $query = sprintf(
                    "%s %s %s %s",
                    "SELECT k.column_name FROM information_schema.table_constraints t",
                    "JOIN information_schema.key_column_usage k USING(constraint_name,table_schema,table_name)",
                    "WHERE t.constraint_type='PRIMARY KEY' AND t.table_schema='".$connection->getDatabase()."'",
                    "AND t.table_name='".$rootEntity."';"
                );
                $stmt = $connection->executeQuery($query);

                return $stmt->fetchColumn();
            case 'sqlite':
                $query = "PRAGMA table_info(".$rootEntity.")";
                $stmt = $connection->executeQuery($query);
                while ($row = $stmt->fetch()) {
                    if ($row['pk']) {
                        return $row['name'];
                    }
                }
        }

        return null;
    }

    /**
     * @param ORMQueryBuilder|DBALQueryBuilder $qb
     * @param string                           $partName
     *
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
     * @return bool
     */
    public function isWithoutTotalResultCount()
    {
        return $this->withoutTotalResultCount;
    }

    /**
     * @param bool $withoutTotalResultCount
     */
    public function setWithoutTotalResultCount($withoutTotalResultCount)
    {
        $this->withoutTotalResultCount = (bool) $withoutTotalResultCount;
    }

    /**
     * Sets a query hint. If the hint name is not recognized, it is silently ignored.
     *
     * @param string $name  The name of the hint.
     * @param mixed  $value The value of the hint.
     *
     * @return static This instance.
     */
    public function setHint($name, $value)
    {
        $this->hints[$name] = $value;

        return $this;
    }

    /**
     * @param ORMQueryBuilder|DBALQueryBuilder $qb
     * @param array|null                       $parts
     *
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
     * @param string $condition
     *
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

    /**
     * @return void
     */
    protected function parseSelectPath()
    {
        $selects = [];
        foreach ($this->getSqlPart($this->queryBuilder, 'select') as $part) {
            $selects = array_merge(array_map('trim', explode(',', $part)), $selects);
        }
        $selects = array_map(
            function ($str) {
                return preg_replace(
                    '/ {2,}/',
                    ' ',
                    //strtolower(str_replace([' AS ', ' as ', ' HIDDEN '], [' ', ' ', ' '], $str))
                    str_replace([' AS ', ' as ', ' HIDDEN '], [' ', ' ', ' '], $str)
                );
            },
            $selects
        );
        foreach ($selects as $select) {
            $array = explode(' ', $select);
            $this->aliasMapping[isset($array[1]) ? $array[1] : $array[0]] = $array[0];
        }
    }
}
