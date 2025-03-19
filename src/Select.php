<?php

declare(strict_types=1);

namespace Laminas\Db\Paginator\Adapter;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface as DBAdapterInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql;
use Laminas\Paginator\Adapter\AdapterInterface;
use Laminas\Paginator\Adapter\Exception\MissingRowCountColumnException;
use Laminas\Paginator\Exception;

use function array_key_exists;
use function is_array;
use function iterator_to_array;
use function strtolower;

/**
 * @template-covariant TKey of int
 * @template-covariant TValue
 * @implements AdapterInterface<TKey, TValue>
 */
class Select implements AdapterInterface
{
    protected Sql\Sql $sql;

    /**
     * Database query
     */
    protected Sql\Select $select;

    /**
     * Database count query
     */
    protected ?Sql\Select $countSelect;

    protected ResultSetInterface $resultSetPrototype;
    public const ROW_COUNT_COLUMN_NAME = 'C';

    /**
     * Total item count
     */
    protected int $rowCount = 0;

    /**
     * Constructs instance.
     *
     * @param Sql\Select $select The select query
     * @param DBAdapterInterface|Sql\Sql $adapterOrSqlObject DB adapter or Sql\Sql object
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        Sql\Select $select,
        Sql\Sql|DBAdapterInterface $adapterOrSqlObject,
        ?ResultSetInterface $resultSetPrototype = null,
        ?Sql\Select $countSelect = null
    ) {
        $this->select      = $select;
        $this->countSelect = $countSelect;

        if ($adapterOrSqlObject instanceof Adapter) {
            $adapterOrSqlObject = new Sql\Sql($adapterOrSqlObject);
        }

        if (! $adapterOrSqlObject instanceof Sql\Sql) {
            throw new Exception\InvalidArgumentException(
                '$adapterOrSqlObject must be an instance of Laminas\Db\Adapter\Adapter or Laminas\Db\Sql\Sql'
            );
        }

        $this->sql                = $adapterOrSqlObject;
        $this->resultSetPrototype = $resultSetPrototype ?: new ResultSet();
    }

    /**
     * Returns an array of items for a page.
     *
     * Executes the {$itemsCallback}.
     *
     * @inheritDoc
     */
    public function getItems($offset, $itemCountPerPage): array
    {
        $select = clone $this->select;
        $select
            ->offset($offset)
            ->limit($itemCountPerPage);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        $resultSet = clone $this->resultSetPrototype;
        $resultSet->initialize($result);

        return iterator_to_array($resultSet);
    }

    /**
     * Returns the total number of rows in the result set.
     */
    public function count(): int
    {
        $select    = $this->getSelectCount();
        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();
        $row       = $result->current();
        if (! is_array($row)) {
            throw MissingRowCountColumnException::forColumn(self::ROW_COUNT_COLUMN_NAME);
        }
        $this->rowCount = $this->locateRowCount($row);

        return $this->rowCount;
    }

    /**
     * Returns select query for count
     */
    protected function getSelectCount(): Sql\Select
    {
        if ($this->countSelect !== null) {
            return $this->countSelect;
        }

        $select = clone $this->select;
        $select->reset(Sql\Select::LIMIT);
        $select->reset(Sql\Select::OFFSET);
        $select->reset(Sql\Select::ORDER);

        $countSelect = new Sql\Select();

        $countSelect->columns([self::ROW_COUNT_COLUMN_NAME => new Sql\Expression('COUNT(1)')]);
        $countSelect->from(['original_select' => $select]);

        return $countSelect;
    }

    /**
     * @throws MissingRowCountColumnException
     */
    private function locateRowCount(array $row): int
    {
        if (array_key_exists(self::ROW_COUNT_COLUMN_NAME, $row)) {
            return (int) $row[self::ROW_COUNT_COLUMN_NAME];
        }

        $lowerCaseColumnName = strtolower(self::ROW_COUNT_COLUMN_NAME);
        if (array_key_exists($lowerCaseColumnName, $row)) {
            return (int) $row[$lowerCaseColumnName];
        }

        throw MissingRowCountColumnException::forColumn(self::ROW_COUNT_COLUMN_NAME);
    }

    /**
     * @internal
     *
     * @see https://github.com/laminas/laminas-paginator/issues/3 Reference for creating an internal cache ID
     *
     * @todo The next major version should rework the entire caching of a paginator.
     */
    public function getArrayCopy(): array
    {
        return [
            'select'       => $this->sql->buildSqlString($this->select),
            'count_select' => $this->sql->buildSqlString(
                $this->getSelectCount()
            ),
        ];
    }
}
