<?php

declare(strict_types=1);

namespace LaminasTest\Db\Paginator\Adapter;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Platform\Sql92;
use Laminas\Db\Paginator\Adapter\Select;
use Laminas\Db\Paginator\Adapter\TableGateway;
use Laminas\Db\TableGateway\TableGateway as BaseTableGateway;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DbTableGatewayTest extends TestCase
{
    /** @var MockObject|StatementInterface */
    protected $mockStatement;

    /** @var TableGateway */
    protected $dbTableGateway;

    /** @var MockObject|BaseTableGateway */
    protected $mockTableGateway;

    #![Override]
    public function setup(): void
    {
        $mockStatement = $this->createMock(StatementInterface::class);
        $mockDriver    = $this->createMock(DriverInterface::class);
        $mockDriver
            ->expects($this->any())
            ->method('createStatement')
            ->willReturn($mockStatement);
        $mockDriver
            ->expects($this->any())
            ->method('formatParameterName')
            ->willReturnArgument(0);

        $mockAdapter = $this->getMockForAbstractClass(
            Adapter::class,
            [$mockDriver, new Sql92()]
        );

        $tableName        = 'foobar';
        $mockTableGateway = $this->getMockForAbstractClass(
            BaseTableGateway::class,
            [$tableName, $mockAdapter]
        );

        $this->mockStatement    = $mockStatement;
        $this->mockTableGateway = $mockTableGateway;
    }

    public function testGetItems(): void
    {
        $this->dbTableGateway = new TableGateway($this->mockTableGateway);

        $mockResult = $this->createMock(ResultInterface::class);
        $this->mockStatement
            ->expects($this->any())
            ->method('execute')
            ->willReturn($mockResult);

        $items = $this->dbTableGateway->getItems(2, 10);
        $this->assertEquals([], $items);
    }

    public function testCount(): void
    {
        $this->dbTableGateway = new TableGateway($this->mockTableGateway);

        $mockResult = $this->createMock(ResultInterface::class);
        $mockResult
            ->expects($this->any())
            ->method('current')
            ->willReturn([Select::ROW_COUNT_COLUMN_NAME => 10]);

        $this->mockStatement
            ->expects($this->any())
            ->method('execute')
            ->willReturn($mockResult);

        $count = $this->dbTableGateway->count();
        $this->assertEquals(10, $count);
    }

    public function testGetItemsWithWhereAndOrder(): void
    {
        $where                = "foo = bar";
        $order                = "foo";
        $this->dbTableGateway = new TableGateway($this->mockTableGateway, $where, $order);

        $mockResult = $this->createMock(ResultInterface::class);
        $this->mockStatement
            ->expects($this->any())
            ->method('execute')
            ->willReturn($mockResult);

        $items = $this->dbTableGateway->getItems(2, 10);
        $this->assertEquals([], $items);
    }

    public function testGetItemsWithWhereAndOrderAndGroup(): void
    {
        $where                = "foo = bar";
        $order                = "foo";
        $group                = "foo";
        $this->dbTableGateway = new TableGateway($this->mockTableGateway, $where, $order, $group);

        $mockResult = $this->createMock(ResultInterface::class);
        $this->mockStatement
            ->expects($this->once())
            ->method('setSql')
            ->with(
                $this->equalTo(
                    // phpcs:ignore
                    'SELECT "foobar".* FROM "foobar" WHERE foo = bar GROUP BY "foo" ORDER BY "foo" ASC LIMIT limit OFFSET offset'
                )
            );
        $this->mockStatement
            ->expects($this->any())
            ->method('execute')
            ->willReturn($mockResult); // MixedMethodCall emitted here

        $items = $this->dbTableGateway->getItems(2, 10);
        $this->assertEquals([], $items);
    }

    public function testGetItemsWithWhereAndOrderAndGroupAndHaving(): void
    {
        $where                = "foo = bar";
        $order                = "foo";
        $group                = "foo";
        $having               = "count(foo)>0";
        $this->dbTableGateway = new TableGateway($this->mockTableGateway, $where, $order, $group, $having);

        $mockResult = $this->createMock(ResultInterface::class);
        $this->mockStatement
            ->expects($this->once())
            ->method('setSql')
            ->with(
                $this->equalTo(
                    // phpcs:ignore
                    'SELECT "foobar".* FROM "foobar" WHERE foo = bar GROUP BY "foo" HAVING count(foo)>0 ORDER BY "foo" ASC LIMIT limit OFFSET offset'
                )
            );
        $this->mockStatement
            ->expects($this->any())
            ->method('execute') // MixedMethodCall emitted here
            ->willReturn($mockResult);

        $items = $this->dbTableGateway->getItems(2, 10);
        $this->assertEquals([], $items);
    }
}
