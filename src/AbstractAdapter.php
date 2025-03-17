<?php

declare(strict_types=1);

namespace Laminas\Db\Paginator\Adapter;

use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql;
use Laminas\Paginator\Adapter\AdapterInterface;
use Laminas\Paginator\Adapter\Exception\MissingRowCountColumnException;
use ReturnTypeWillChange;

use function array_key_exists;
use function iterator_to_array;
use function strtolower;

class AbstractAdapter implements AdapterInterface
{
    public const ROW_COUNT_COLUMN_NAME = 'C';

    /** @var Sql\Sql */
    protected Sql\Sql $sql;

    /**
     * Database query
     *
     * @var Sql\Select
     */
    protected Sql\Select $select;

    /**
     * Database count query
     *
     * @var null|Sql\Select
     */
    protected ?Sql\Select $countSelect;

    /** @var ResultSetInterface */
    protected ResultSetInterface $resultSetPrototype;

    /**
     * Total item count
     *
     * @var int|null
     */
    protected ?int $rowCount = null;

    public function getItems($offset, $itemCountPerPage): array
    {
        $select = clone $this->select;
        $select->offset($offset);
        $select->limit($itemCountPerPage);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();

        $resultSet = clone $this->resultSetPrototype;
        $resultSet->initialize($result);

        return iterator_to_array($resultSet);
    }

    /**
     * Returns the total number of rows in the result set.
     *
     * @return int
     */
    #[ReturnTypeWillChange]
    public function count(): int
    {
        if ($this->rowCount !== null) {
            return $this->rowCount;
        }

        $select         = $this->getSelectCount();
        $statement      = $this->sql->prepareStatementForSqlObject($select);
        $result         = $statement->execute();
        $row            = $result->current();
        $this->rowCount = $this->locateRowCount($row);

        return $this->rowCount;
    }

    /**
     * Returns select query for count
     *
     * @return Sql\Select
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
     * @internal
     *
     * @see https://github.com/laminas/laminas-paginator/issues/3 Reference for creating an internal cache ID
     *
     * @todo The next major version should rework the entire caching of a paginator.
     * @return array
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
}
