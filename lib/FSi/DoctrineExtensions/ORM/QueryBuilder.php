<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\ORM;

use Doctrine\ORM\Query as BaseQuery;
use Doctrine\ORM\QueryBuilder as BaseQueryBuilder;

class QueryBuilder extends BaseQueryBuilder
{
    public function getQuery()
    {
        $query = parent::getQuery();
        $query->setHint(BaseQuery::HINT_INCLUDE_META_COLUMNS, true);

        return $query;
    }
}
