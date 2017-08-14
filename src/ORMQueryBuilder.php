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
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
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
use Zk2\SpsComponent\Doctrine\SortableNullsWalker;

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
    }

    /**
     * @param ContainerInterface $container
     *
     * @return $this
     */
    public function buildWhere(ContainerInterface $container)
    {
        $condition = $this->doBuildWhere($container);
        if ($this->condition = $this->trimAndOr($condition)) {
            $this->queryBuilder->andWhere($this->condition);
            if ($this->parameters->count()) {
                $this->queryBuilder->setParameters($this->parameters);
            }
        }

        return $this;
    }

    /**
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function getResult($limit = 0, $offset = 0)
    {
        if ($limit > 0 and false === $this->limitOffset($limit, $offset)) {
            return [];
        }

        $this->result = $this->queryBuilder
            ->getQuery()
            ->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SortableNullsWalker::class)
            ->getResult();

        return $this->result;
    }

    /**
     * @return int
     *
     * @throws QueryBuilderException
     */
    private function count()
    {
        if (!$this->withoutTotalResultCount) {
            $qb = clone $this->queryBuilder;
            $this->resetSqlParts($qb, ['select', 'groupBy', 'orderBy'])
                ->setFirstResult(null)
                ->setMaxResults(null)
                ->select(sprintf('COUNT(DISTINCT %s)', $this->aliasDotPrimary()));
            try {
                $this->totalResultCount = $qb->getQuery()->getSingleScalarResult();
            } catch (NoResultException $e) {
                $this->totalResultCount = 0;
            } catch (\Exception $e) {
                throw new QueryBuilderException($e->getMessage());
            }
        }

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
        if (!$this->withoutTotalResultCount and !$this->count()) {
            return false;
        }

        $qb = clone $this->queryBuilder;
        $qb->select(sprintf('DISTINCT %s', $this->aliasDotPrimary()))
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        /** @var OrderBy $order */
        foreach ($this->getSqlPart($qb, 'orderBy') as $order) {
            foreach ($order->getParts() as $part) {
                if (!strlen($part)) {
                    continue;
                }
                $arr = explode(' ', $part);
                $field = (string) $arr[0];
                if ($this->aliasDotPrimary() === $field) {
                    continue;
                }
                $qb->addSelect($field);
            }
        }

        $ids = array_map(
            function ($val) {
                return $val[$this->primary];
            },
            $qb
                ->getQuery()
                ->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SortableNullsWalker::class)
                ->getResult()
        );
        if (!$ids) {
            return false;
        }

        $this->queryBuilder
            ->setFirstResult(null)
            ->setMaxResults(null)
            ->where(sprintf("%s IN (:_sps_ids_)", $this->aliasDotPrimary()))
            ->setParameters(['_sps_ids_' => $ids]);

        return true;
    }

    /**
     * @return $this
     *
     * @throws QueryBuilderException
     */
    private function initRoot()
    {
        $rootEntities = $this->queryBuilder->getRootEntities();
        $rootAliases = $this->queryBuilder->getRootAliases();
        if (!count($rootEntities) or !count($rootAliases)) {
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
     * @param ContainerInterface $container
     *
     * @return string
     */
    private function doBuildWhere(ContainerInterface $container)
    {
        $this->initRoot();
        $condition = '';
        if ($container->getCondition()) {
            $condition .= $this->buildCondition($container);
        } else {
            foreach ($container->getCollectionOfConditions() as $subContainer) {
                if (ContainerInterface::CONDITION_NAME === $subContainer->getType()) {
                    $condition .= $this->buildCondition($subContainer);
                } elseif (ContainerInterface::COLLECTION_NAME === $subContainer->getType()) {
                    $condition .= $this->doBuildWhere($subContainer);
                }
            }
        }
        if (!$condition = $this->trimAndOr($condition)) {
            return null;
        }

        return sprintf(
            '%s(%s)',
            $container->getAndOr() ? sprintf(' %s ', $container->getAndOr()) : null,
            $condition
        );
    }

    /**
     * @param ContainerInterface $container
     *
     * @return string
     *
     * @throws ContainerException
     */
    private function buildCondition(ContainerInterface $container)
    {
        if (!$condition = $container->getCondition()) {
            throw new ContainerException('Condition is empty');
        }

        $suffix = $this->parameters->count() + 1;
        $condition->reconfigureParameters($suffix);

        if ($customFunction = $condition->getCustomFunction()) {
            $this->addCustomFunction($customFunction['name'], $customFunction['class'], $customFunction['type']);
        }

        if ($condition->getFunction() and $this->isAggregateFunction($condition->getFunction())) {
            $where = $this->aggregate($condition);
        } else {
            $where = $condition->buildCondition();
            foreach ($condition->getParameters() as $paramName => $paramValue) {
                $parameter = new Parameter($paramName, $paramValue);
                $this->parameters->add($parameter);
            }
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
     * @param ConditionInterface $condition
     *
     * @return string
     */
    private function aggregate(ConditionInterface $condition)
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

        if (count($newParameters) === 2 and stripos($condition->getComparisonOperator(), Condition::BETWEEN) !== false) {
            $newParameterName = implode(' AND ', array_keys($newParameters));
        } else {
            $newParameterName = key($newParameters);
        }

        $newHaving = sprintf(
            '%s(%s) %s %s',
            $condition->getFunction(),
            $prefix.$condition->getProperty(),
            $condition->getComparisonOperator(),
            $newParameterName
        );
        $qb->andHaving($newHaving)->setParameters([]);

        foreach ($newParameters as $paramName => $paramValue) {
            $parameter = new Parameter($paramName, $paramValue);
            $this->parameters->add($parameter);
        }

        return sprintf('%s IN(%s)', $this->aliasDotPrimary(), $qb->getDQL());
    }
}
