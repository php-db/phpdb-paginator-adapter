# The Select adapter

The `Select` adapter allows you to provide a `Select` statement for pulling a dataset, and optionally a `Select` statement for pulling a count of results, and an optional `Select` statement for providing an overall count of items.

The adapter does **not** fetch all records from the database in order to count them, nor does it run any queries immediately.
If no `Select` instance was provided for counting results, the adapter manipulates the original `Select` to produce a corresponding `COUNT` query, and uses the new query to get the number of rows.
While this approach requires an extra round-trip to the database, doing so is still many times faster than fetching an entire result set and using `count()`, especially with large collections of data.

## Creating An Instance

The `Select` constructor has the following signature:

```php
public function __construct(
    \Laminas\Db\Sql\Select $select,
    \Laminas\Db\Adapter\AdapterInterface|\Laminas\Db\Sql\Sql $adapterOrSqlObject,
    ?\Laminas\Db\ResultSet\ResultSetInterface $resultSetPrototype = null,
    ?\Laminas\Db\Sql\Select $countSelect = null
)
```

The first argument is the `Select` to use when retrieving results to paginate.
The next argument, `$adapterOrSqlObject`, provides access to the adapter so it can execute the `Select` statement against the actual database.
The third argument is a specific result set type to use on results returned from the `Select` operation; these allow you to customize the items returned, if desired.
(See the [laminas-db ResultSet documentation for more details](https://docs.laminas.dev/laminas-db/result-set/).)
The fourth argument allows you to specify a specific `Select` instance to use to provide a total count of results.

### Using the AdapterPluginManager

By default, when pulling the `Laminas\Paginator\AdapterPluginManager` from the application DI container, it is aware of the `Select` adapter.
You can retrieve an instance from the plugin manager via its `get()` method, passing any constructor arguments you want to provide via an array as the second argument:

```php
use Laminas\Paginator\AdapterPluginManager;
use Laminas\Db\Paginator\Adapter\Select;

// $container is the PSR-11 container associated with the application.
$pluginManager = $container->get(AdapterPluginManager::class);

// $select is the laminas-db Select instance for retrieving items
// $dbAdapter is the laminas-db adapter you want to use
$adapter = $pluginManager->get(Select::class, [
    $select,
    $dbAdapter
]);
```

All required arguments to the constructor must be passed in the array, and they will be passed in the same order to the constructor.

## Modifying Result Items

The default `Laminas\Db\ResultSet\ResultSet` used when iterating over items returns each item as an associative array.
If you wish to filter out specific fields, modify the column names, or return something other than an associative array, you will need to provide a different `Laminas\Db\ResultSet\ResultSetInterface` implementation to the constructor, or extend the adapter and override the `getItems()` method.

### Providing an Alternate ResultSet

You can override the default `ResultSet` implementation by passing an object implementing `Laminas\Db\ResultSet\ResultSetInterface` as the third constructor argument to the `Select` adapter:

```php
use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Db\Paginator\Adapter\Select;
use Laminas\Paginator\Paginator;

// $objectPrototype is an instance of our custom entity
// $hydrator is a custom hydrator for our entity
// (implementing Laminas\Hydrator\HydratorInterface)
$resultSet = new HydratingResultSet($hydrator, $objectPrototype);

// $query is our Select statement
// $dbAdapter is the laminas-db adapter instance
$adapter   = new Select($query, $dbAdapter, $resultSet)
$paginator = new Laminas\Paginator\Paginator($adapter);
```

Now when we iterate over `$paginator`, we will get instances of our custom entity instead of associative arrays.

### Overriding getItems

If you want to manipulate the results manually, you can extend the adapter and override the `getItems()` method directly.
The following example demonstrates using an `array_map()` operation on results in order to cast the rows to an object.
It assumes the class `App\Fuzz` exists, and defines a static method `fromArray()` that will allow casting an array to an `App\Fuzz` instance.

```php
namespace App;

use Laminas\Db\Paginator\Adapter\Select;

class FuzzSelect extends Select
{
    public function getItems($offset, $itemCountPerPage)
    {
        return array_map(
            function (array $row): Fuzz {
              return Fuzz::fromArray($row);
            },
            parent::getItems($offset, $itemCountPerPage)
        );
    }
}
```

## Counting Total Items

The database adapter will try and build the most efficient query that will execute on pretty much any modern database.
However, depending on your database or even your own schema setup, there might be more efficient ways to get a rowcount.

There are two approaches for doing this: providing an additional `Select` instance for retrieving a count to the constructor, or overriding the `count()` method.

### Providing a Select for Counting

You can pass an additional `Laminas\Db\Sql\Select` object as the fourth constructor argument to the `Select` adapter to implement a custom count query.

For example, if you keep track of the count of blog posts in a separate table, you could achieve a faster count query with the following setup:

```php
use Laminas\Db\Sql\Select;
use Laminas\Db\Paginator\Adapter\Select;
use Laminas\Paginator\Paginator;

$countQuery = new Select();
$countQuery
    ->from('item_counts')
    ->columns([Select::ROW_COUNT_COLUMN_NAME => 'post_count']);

// $query is the Select for retrieving items
// $dbAdapter is the laminas-db adapter
$adapter   = new Select($query, $dbAdapter, null, $countQuery);
$paginator = new Paginator($adapter);
```

This approach will probably not give you a huge performance gain on small collections and/or simple select queries.
However, with complex queries and large collections, a similar approach could give you a significant performance boost.

### Overriding the Count Method

The following example demonstrates extending the `Select` adapter to override the `count()` method.

```php
namespace App;

use Laminas\Db\Sql\Select;
use Laminas\Db\Paginator\Adapter\Select;

class MySelect extends Select
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

// $query is the Select for retrieving items
// $dbAdapter is the laminas-db adapter
$adapter = new MySelect($query, $dbAdapter);
```
