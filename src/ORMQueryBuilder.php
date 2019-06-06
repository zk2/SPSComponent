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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Base;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Zk2\SpsComponent\Condition\Condition;
use Zk2\SpsComponent\Condition\ConditionInterface;
use Zk2\SpsComponent\Condition\ContainerException;
use Zk2\SpsComponent\Condition\ContainerInterface;

/**
 *
 */
class ORMQueryBuilder extends AbstractQueryBuilder implements QueryBuilderInterface
{
    /**
     * @var ArrayCollection|Parameter[]
     */
    protected $parameters;
    /**
     * @var array
     */
    protected $originalParameters;

    /**
     * ORMQueryBuilder constructor.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @throws QueryBuilderException
     */
    public function __construct(QueryBuilder $queryBuilder)
    {
        parent::__construct($queryBuilder);
        $this->parameters = new ArrayCollection();
        $this->originalParameters = $this->queryBuilder->getParameters();
        $this->originalParametersTypes = $this->queryBuilder->getParameterTypes();
    }

    /**
     * @param ContainerInterface $container
     *
     * @return $this
     *
     * @throws QueryBuilderException
     */
    public function buildWhere(ContainerInterface $container)
    {
        $this->initRoot();
        $condition = $this->doBuildWhere($container);
        if ($this->condition = $this->trimAndOr($condition)) {
            $this->queryBuilder->andWhere($this->condition);
            foreach ($this->parameters as $parameter) {
                $this->queryBuilder->getParameters()->add($parameter);
            }
        }

        return $this;
    }

    /**
     * @param int $limit
     * @param int $offset
     *
     * @return array
     *
     * @throws QueryBuilderException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getResult($limit = 0, $offset = 0)
    {
        $this->count();
        $this->queryBuilder->setFirstResult($offset)->setMaxResults($limit);

        if ($limit > 0) {
            $pathOrderBy = $this->getSqlPart($this->queryBuilder, 'orderBy');
            $this->resetSqlParts($this->queryBuilder, ['orderBy']);
            $qb = $this->cloneQb($this->queryBuilder);
            $qb->select(sprintf('%s', $this->aliasDotPrimary()))
                ->setFirstResult(null)
                ->setMaxResults(null);

            /** @var OrderBy $part */
            foreach ($pathOrderBy as $part) {
                foreach ($part->getParts() as $order) {
                    $out = [];
                    preg_match('/(.*[^\s])?\s+(asc|desc)/i', $order, $out);
                    $property = $out[1];
                    $direction = $out[2];
                    $alias = ($key = array_search($property, $this->aliasMapping)) ?: $property;
                    if ($this->isAggFunc($property)) {
                        $qb->addSelect($property.' AS '.$alias);
                        $qb->addOrderBy($alias, $direction);
                        $this->queryBuilder->addOrderBy($alias, $direction);
                    } else {
                        $qb->addOrderBy($property, $direction);
                        $this->queryBuilder->addOrderBy($property, $direction);
                    }
                }
            }

            $query = $qb->getQuery();

            foreach ($this->hints as $name => $hint) {
                $query->setHint($name, $hint);
            }

            $params = $types = [];
            /** @var Parameter $parameter */
            foreach ($query->getParameters() as $key => $parameter) {
                $params[$key] = $parameter->getValue();
                $types[$key] = $parameter->getType();
            }

            /** @var \PDOStatement $stmt */
            $stmt = $this->queryBuilder->getEntityManager()->getConnection()->executeQuery(
                $query->getSQL(),
                $params,
                $types
            );

            $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
            $ids = array_values(array_unique($ids));

            if (!$ids) {
                return [];
            }
            $ids = array_slice($ids, $offset, $limit);
            $this->queryBuilder
                ->setFirstResult(null)
                ->setMaxResults(null)
                ->where(sprintf("%s IN (:_sps_ids_)", $this->aliasDotPrimary()))
                ->setParameters(['_sps_ids_' => $ids]);
        }
        $query = $this->queryBuilder->getQuery();
        foreach ($this->hints as $name => $hint) {
            $query->setHint($name, $hint);
        }
        $this->result = $query->getResult();
        if (!$this->totalResultCount) {
            $this->totalResultCount = count($this->result);
        }

        return $this->result;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return string
     *
     * @throws ContainerException
     */
    protected function buildCondition(ContainerInterface $container)
    {
        if (!$condition = $container->getCondition()) {
            throw new ContainerException('Condition is empty');
        }

        $suffix = $this->parameters->count() + 1;
        $condition->reconfigureParameters($suffix);

        if ($condition->isAggregateFunction()) {
            $where = $this->aggregate($condition);
        } else {
            $where = $condition->buildCondition();
            $this->addParameter($condition->getParameters());
        }
        if (!$where = $this->trimAndOr($where)) {
            return null;
        }

        return sprintf(
            '%s%s',
            $container->getAndOr() ? sprintf(' %s ', $container->getAndOr()) : null,
            $where
        );
    }

    /**
     * @param array $parameters
     *
     * @return void
     */
    protected function addParameter(array $parameters)
    {
        foreach ($parameters as $paramName => $paramValue) {
            $parameter = new Parameter($paramName, $paramValue);
            $this->parameters->add($parameter);
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
        $qb = $this->cloneQb($this->queryBuilder);

        $this->resetSqlParts($qb)
            ->select(sprintf('DISTINCT %s%s', $prefix, $this->aliasDotPrimary()))
            ->addGroupBy(sprintf('%s%s', $prefix, $this->aliasDotPrimary()));
        /** @var From $fromPart */
        foreach ($this->getSqlPart($this->queryBuilder, 'from') as $fromPart) {
            /** @var Base $newFrom */
            $newFrom = new From($fromPart->getFrom(), $prefix.$fromPart->getAlias());
            $qb->add('from', $newFrom, true);
        }
        foreach ($this->getSqlPart($this->queryBuilder, 'join') as $rootAlias => $joinPart) {
            /** @var Join $subPart */
            foreach ($joinPart as $subPart) {
                $join = new Join(
                    $subPart->getJoinType(),
                    $prefix.$subPart->getJoin(),
                    $prefix.$subPart->getAlias()
                );
                /** @var Base $newJoin */
                $newJoin = [$prefix.$rootAlias => $join];
                $qb->add('join', $newJoin, true);
            }
        }

        $newParameters = [];
        foreach ($condition->getParameters() as $paramName => $paramValue) {
            $newParameters[str_replace(':', ':'.$prefix, $paramName)] = $paramValue;
        }

        if (count($newParameters) === 2 && stripos($condition->getComparisonOperator(), Condition::BETWEEN) !== false) {
            $newParameterName = implode(' AND ', array_keys($newParameters));
        } else {
            $newParameterName = key($newParameters);
        }

        $qb->andHaving($condition->getSqlFunctionDefinition($newParameterName, $prefix))->setParameters([]);

        foreach ($newParameters as $paramName => $paramValue) {
            $parameter = new Parameter($paramName, $paramValue);
            $this->parameters->add($parameter);
        }

        return sprintf('%s IN(%s)', $this->aliasDotPrimary(), $qb->getDQL());
    }

    /**
     * @return int
     *
     * @throws QueryBuilderException
     */
    private function count()
    {
        if (!$this->withoutTotalResultCount) {
            $qb = $this->cloneQb($this->queryBuilder);
            $this->resetSqlParts($qb, ['select', 'groupBy', 'orderBy'])
                ->setFirstResult(null)
                ->setMaxResults(null)
                ->select(sprintf('COUNT(DISTINCT %s)', $this->aliasDotPrimary()));
            try {
                $this->totalResultCount = $qb->getQuery()->getSingleScalarResult();
            } catch (NonUniqueResultException $e) {
                $this->totalResultCount = 0;
            } catch (\Exception $e) {
                throw new QueryBuilderException($e->getMessage());
            }
        }

        return $this->totalResultCount;
    }

    /**
     * @return $this
     *
     * @throws QueryBuilderException
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    private function initRoot()
    {
        $rootEntities = $this->queryBuilder->getRootEntities();
        $rootAliases = $this->queryBuilder->getRootAliases();
        if (!count($rootEntities) || !count($rootAliases)) {
            throw new QueryBuilderException('Path "FROM" in query is empty');
        }
        $this->rootEntity = $rootEntities[0];
        $this->rootAlias = $rootAliases[0];
        $this->primary = $this->queryBuilder
            ->getEntityManager()
            ->getClassMetadata($this->rootEntity)
            ->getSingleIdentifierFieldName();

        return $this;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return QueryBuilder
     */
    private function cloneQb(QueryBuilder $qb)
    {
        $newQb = clone $qb;

        foreach ($qb->getQuery()->getHints() as $hintName => $hint) {
            $newQb->getQuery()->setHint($hintName, $hint);
        }

        return $newQb;
    }
}
