<?php
/**
 * This file is part of the SpsComponent package.
 *
 * (c) Evgeniy Budanov <budanov.ua@gmail.comm> 2017.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Doctrine;

use Doctrine\ORM\Query\AST\OrderByItem;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Class SortableNullsWalker
 */
class SortableNullsWalker extends SqlWalker
{
    const NULLS_FIRST = 'NULLS FIRST';
    const NULLS_LAST = 'NULLS LAST';

    /**
     * @param OrderByItem $orderByItem
     *
     * @return string
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function walkOrderByItem($orderByItem)
    {
        $sql = parent::walkOrderByItem($orderByItem);
        $hint = $this->getQuery()->getHint('SortableNullsWalker.fields');
        $expr = $orderByItem->expression;
        $type = strtoupper($orderByItem->type);
        if (is_array($hint) && count($hint)) {
            if ($expr instanceof PathExpression and $expr->type === PathExpression::TYPE_STATE_FIELD) {
                $platform = $this->getConnection()->getDatabasePlatform()->getName();
                $fieldName = $expr->field;
                $dqlAlias = $expr->identificationVariable.(!empty($parts) ? '.'.implode('.', $parts) : '');
                $index = $dqlAlias.'.'.$fieldName;
                switch ($platform) {
                    case 'mysql':
                        $nullDirection = self::NULLS_LAST === $hint[$index] ? 'ASC' : 'DESC';
                        $sql = sprintf(
                            'ISNULL(%s) %s, %s %s',
                            $this->walkPathExpression($expr),
                            $nullDirection,
                            $this->walkPathExpression($expr),
                            $type
                        );
                        break;
                    case 'oracle':
                    case 'postgresql':
                        $search = $this->walkPathExpression($expr).' '.$type;
                        $sql = str_replace($search, $search.' '.$hint[$index], $sql);
                        break;
                    default:
                        break;
                }
            }
        }

        return $sql;
    }
}
