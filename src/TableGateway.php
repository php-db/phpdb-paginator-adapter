<?php

declare(strict_types=1);

namespace Laminas\Db\Paginator\Adapter;

use Closure;
use Laminas\Db\Sql\Having;
use Laminas\Db\Sql\Where;
use Laminas\Db\TableGateway\AbstractTableGateway;

/**
 * @template-covariant TKey of int
 * @template-covariant TValue
 * @extends Select<TKey, TValue>
 */
class TableGateway extends Select
{
    /**
     * Constructs instance.
     */
    public function __construct(
        AbstractTableGateway $tableGateway,
        Where|array|Closure|string|null $where = null,
        array|string|null $order = null,
        array|string|null $group = null,
        Having|array|Closure|string|null $having = null
    ) {
        $this->sql    = $tableGateway->getSql();
        $this->select = $this->sql->select();

        if ($where !== null) {
            $this->select->where($where);
        }

        if ($order !== null) {
            $this->select->order($order);
        }

        if ($group !== null) {
            $this->select->group($group);
        }

        if ($having !== null) {
            $this->select->having($having);
        }

        $this->resultSetPrototype = $tableGateway->getResultSetPrototype();
        $this->countSelect        = null;

        parent::__construct(
            $this->select,
            $tableGateway->getAdapter(),
            $this->resultSetPrototype,
            $this->countSelect
        );
    }
}
