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
use Doctrine\DBAL\Query\QueryBuilder;
use Zk2\SpsComponent\Condition\ConditionInterface;
use Zk2\SpsComponent\Condition\ContainerException;
use Zk2\SpsComponent\Condition\ContainerInterface;

/**
 *
 */
class DBALQueryBuilder extends AbstractQueryBuilder implements QueryBuilderInterface
{

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var array
     */
    protected $parametersTypes = [];
    /**
     * @var array
     */
    protected $originalParameters;

    /**
     * @var array
     */
    protected $originalParametersTypes = [];

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(QueryBuilder $queryBuilder)
    {
        parent::__construct($queryBuilder);
        $this->parameters = [];
        $this->originalParameters = $this->queryBuilder->getParameters();
        $this->originalParametersTypes = $this->queryBuilder->getParameterTypes();
    }

    /**
     * @param ContainerInterface $container
     *
     * @return $this
     */
    public function buildWhere(ContainerInterface $container)
    {
        $this->initRoot();
        $condition = $this->doBuildWhere($container);
        if ($this->condition = $this->trimAndOr($condition)) {
            $this->queryBuilder->andWhere($this->condition);
            if (count($this->parameters)) {
                $this->queryBuilder->setParameters(
                    array_merge($this->originalParameters, $this->parameters),
                    array_merge($this->originalParametersTypes, $this->parametersTypes)
                );
            }
        }

        return $this;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param int $mode
     *
     * @return array
     */
    public function getResult($limit = 0, $offset = 0, $mode = \PDO::FETCH_ASSOC)
    {
        if ($limit > 0 && false === $this->limitOffset($limit, $offset)) {
            return [];
        }

        $stmt = $this->queryBuilder->execute();
        $this->result = $stmt->fetchAll($mode);

        return $this->result;
    }

    /**
     * @param ContainerInterface $container
     * @param int                $suffix
     *
     * @return string
     *
     * @throws ContainerException
     */
    protected function buildCondition(ContainerInterface $container, $suffix = 0)
    {
        $suffix = count($this->parameters) + 1;

        return parent::buildCondition($container, $suffix);
    }

    /**
     * @param array $parameters
     *
     * @return void
     */
    protected function addParameter(array $parameters)
    {
        foreach ($parameters as $paramName => $paramValue) {
            $this->parameters[$paramName] = $paramValue;
            $this->parametersTypes[$paramName] = $this->inferType($paramValue);
        }
    }

    /**
     * @param ConditionInterface $condition
     *
     * @return string
     */
    protected function aggregate(ConditionInterface $condition)
    {
        if (!$condition->getParameters()) {
            return '';
        }
        $this->aggNumber++;
        $prefix = str_repeat('_', $this->aggNumber);
        $qb = clone $this->queryBuilder;

        $this->resetSqlParts($qb)
            ->select(sprintf('DISTINCT %s%s', $prefix, $this->aliasDotPrimary()))
            ->addGroupBy(sprintf('%s%s', $prefix, $this->aliasDotPrimary()));
        foreach ($this->getSqlPart($this->queryBuilder, 'from') as $fromPart) {
            $fromPart['alias'] = $prefix.$fromPart['alias'];
            $qb->add('from', $fromPart, true);
        }
        foreach ($this->getSqlPart($this->queryBuilder, 'join') as $rootAlias => $joinPart) {
            foreach ($joinPart as $subPart) {
                $subPart['joinCondition'] = $prefix.str_replace(
                    [' '.$rootAlias.'.', ' '.$subPart['joinAlias'].'.'],
                    [' '.$prefix.$rootAlias.'.', ' '.$prefix.$subPart['joinAlias'].'.'],
                    $subPart['joinCondition']
                );
                $subPart['joinAlias'] = $prefix.$subPart['joinAlias'];
                $newJoin = [$prefix.$rootAlias => $subPart];
                /** @var string $newJoin -- HOOK :: The vendor function requires a "@string" parameter */
                $qb->add('join', $newJoin, true);
            }
        }

        $newParameters = $this->paramForAggregate($qb, $condition, $prefix);

        foreach ($newParameters['params'] as $paramName => $paramValue) {
            $this->parameters[$newParameters['name']] = $paramValue;
            $this->parametersTypes[$newParameters['name']] = $this->inferType($paramValue);
        }

        return sprintf('%s IN(%s)', $this->aliasDotPrimary(), $qb->getSQL());
    }

    /**
     * @return int
     */
    private function count()
    {
        $qb = clone $this->queryBuilder;
        $this->resetSqlParts($qb, ['orderBy'])
            ->setFirstResult(null)
            ->setMaxResults(null);
        $connection = $qb->getConnection();
        $sql = sprintf(/** @lang text */"SELECT COUNT(*) FROM (%s) _sps_cnt_", $qb->getSQL());
        $stmt = $connection->executeQuery($sql, $qb->getParameters(), $this->parametersTypes);
        $this->totalResultCount = $stmt->fetchColumn();

        return $this->totalResultCount;
    }

    /**
     * @param int $limit
     * @param int $offset
     *
     * @return bool
     */
    private function limitOffset($limit, $offset)
    {
        if (!$this->withoutTotalResultCount && !$this->count()) {
            return false;
        }

        $this->queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return true;
    }

    /**
     * @return $this
     *
     * @throws QueryBuilderException
     */
    private function initRoot()
    {
        $partFrom = $this->getSqlPart($this->queryBuilder, 'from');
        if (!count($partFrom)) {
            throw new QueryBuilderException('Path "FROM" in query is empty');
        }
        $this->rootEntity = $partFrom[0]['table'];
        $this->rootAlias = $partFrom[0]['alias'];
        $this->primary = $this->getPrimaryKeyName($this->rootEntity) ?: 'id';

        return $this;
    }

    /**
     * Infers type of a given value, returning a compatible constant:
     * - PDO (\PDO::PARAM*)
     * - Connection (\Doctrine\DBAL\Connection::PARAM_*)
     *
     * @param mixed $value Parameter value.
     *
     * @return mixed Parameter type constant.
     */
    private function inferType($value)
    {
        if (is_integer($value)) {
            return \PDO::PARAM_INT;
        }

        if (is_bool($value)) {
            return \PDO::PARAM_BOOL;
        }

        if (is_array($value)) {
            return is_integer(current($value))
                ? Connection::PARAM_INT_ARRAY
                : Connection::PARAM_STR_ARRAY;
        }

        return \PDO::PARAM_STR;
    }
}
