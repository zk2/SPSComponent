<?php

namespace Zk2\SpsComponent\Doctrine;


use Doctrine\ORM\Query\SqlWalker;

class SortableNullsWalker extends SqlWalker
{
    const NULLS_FIRST = 'NULLS FIRST';
    const NULLS_LAST = 'NULLS LAST';

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