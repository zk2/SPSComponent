<?php
/**
 * This file is part of the SpsComponent package.
 *
 * (c) Evgeniy Budanov <budanov.ua@gmail.comm> 2017.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zk2\SpsComponent\Doctrine;

use Doctrine\ORM\Query\SqlWalker;

/**
 * Class SortableNullsWalker
 */
class SortableNullsWalker extends SqlWalker
{
    const NULLS_FIRST = 'NULLS FIRST';
    const NULLS_LAST = 'NULLS LAST';

    /**
     * @param mixed $orderByClause
     *
     * @return string
     */
    public function walkOrderByClause($orderByClause)
    {
        $sql = parent::walkOrderByClause($orderByClause);
        $platform = $this->getConnection()->getDatabasePlatform()->getName();
        switch ($platform) {
            case 'oracle':
            case 'postgresql':
                $sql = preg_replace(['/asc/i', '/desc/i'], ['ASC NULLS FIRST', 'DESC NULLS LAST'], $sql);
                break;
            default:
                break;
        }

        return $sql;
    }
}
