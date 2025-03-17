# The DbTableGateway Adapter

The `TableGateway` adapter allows you to provide a `Laminas\Db\TableGateway\AbstractTableGateway` extension for the purpose of both pulling a dataset and providing a count of results.

By default, it assumes you want to fetch all items from the table.
However, the adapter also allows you to provide WHERE, ORDER BY, GROUP BY, and HAVING clauses (via `Laminas\Db\Sql\Predicate` instances) to refine your selection.

The items returned by the adapter will be based on the `Laminas\Db\ResultSet\ResultSetInterface` result set prototype you associate with the table gateway.

## Creating An Instance

The `TableGateway` constructor has the following signature:

```php
public function __construct(
    \Laminas\Db\TableGateway\AbstractTableGateway $tableGateway,
    null|string|array|\Closure|\Laminas\Db\Sql\Where $where = null,
    null|string|array $order = null,
    null|string|array $group = null,
    null|string|array|\Closure|\Laminas\Db\Sql\Having $having = null
) {
```

The first argument is the `AbstractTableGateway` class extension representing the table you want to fetch results from.
The second argument represents the WHERE criteria for filtering results; see the [laminas-db Where documentation](https://docs.laminas.dev/laminas-db/sql/#where-having) for details on what is accepted.
The third argument represents the order in which results should be sorted; see the [laminas-db "order()" documentation](https://docs.laminas.dev/laminas-db/sql/#order) for details.
The fourth argument represents how results should be grouped; see the [laminas-db Select documentation](https://docs.laminas.dev/laminas-db/sql/#select) for details.
The fifth argument represents a HAVING clause, which is generally used when grouping records; see the [laminas-db Having documentation](https://docs.laminas.dev/laminas-db/sql/#where-having) for more details.

### Using the AdapterPluginManager

By default, when pulling the `Laminas\Paginator\AdapterPluginManager` from the application DI container, it is aware of the `TableGateway` adapter.
You can retrieve an instance from the plugin manager via its `get()` method, passing any constructor arguments you want to provide via an array as the second argument:

```php
use Laminas\Paginator\AdapterPluginManager;
use Laminas\Db\Paginator\Adapter\TableGateway;

// $container is the PSR-11 container associated with the application.
$pluginManager = $container->get(AdapterPluginManager::class);

// $table is the laminas-db TableGateway instance for retrieving items
$adapter = $pluginManager->get(TableGateway::class, [$table]);
```

All required arguments to the constructor must be passed in the array, and they will be passed in the same order to the constructor.

## Counting Total Items

The `TableGateway` adapter extends the [DbSelect adapter](db-select.md); during instatiation, it retrieves both the base `Laminas\Db\Sql\Sql` instance and composed `Laminas\Db\ResultSet\ResultSetInterface` prototype composed in the table gateway, creates a `Laminas\Db\Sql\Select` instance, and passes all three to the parent constructor.
The `Select` instance is thus used as the basis for the count operation as well.

Because there is no way to provide an alternate `Select` for counting, you have two options: extend the `TableGateway` adapter and override the `count()` method, or create your `Select` instances for fetching items and the count and pass them to the `DbSelect` constructor instead.

### Overriding the Count Method

The following example demonstrates extending the `TableGateway` adapter to override the `count()` method.

```php
namespace App;

use Laminas\Db\Sql\Select;
use Laminas\Db\Paginator\Adapter\TableGateway;

class MyTableGateway extends TableGateway
{
    public function count()
    {
        if ($this->rowCount) {
            return $this->rowCount;
        }

        $select = new Select();
        $select
            ->from('item_counts')
            ->columns(['c'=>'post_count']);

        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();
        $row       = $result->current();
        $this->rowCount = $row['c'];

        return $this->rowCount;
    }
}

// $tableGateway is the laminas-db TableGateway for retrieving items
$adapter = new MyTableGateway($tableGateway);
```

### Creating Select Statements to Pass to a DbSelect Adapter

The following example demonstrates pulling the `Sql` instance associated with the `TableGateway` instance, using it to create `Select` instances for pulling items and generating a count, and then using all of them together to create a `DbSelect` instance.

```php
use Laminas\Db\Paginator\Adapter\Select;

// $tableGateway is the laminas-db TableGateway we want to use
$sql    = $tableGateway->getSql();
$select = $sql->select();
// Manipulate the $select to retrieve the result set you want.
// ...
$count = $sql->select();
// Manipulate the $count to generate the item count you want.
// ...

// Create the adapter
$adapter = new Select(
    $select,
    $sql,
    $tableGateway->getResultSetPrototype(),
    $count
);
```
