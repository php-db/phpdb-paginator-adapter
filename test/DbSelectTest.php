<?php

declare(strict_types=1);

namespace LaminasTest\Db\Paginator\Adapter;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Paginator\Adapter\Select;
use Laminas\Db\Sql;
use Laminas\Paginator\Adapter\Exception\MissingRowCountColumnException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function strtolower;

final class DbSelectTest extends TestCase
{
    /** @var MockObject|Sql\Select */
    protected $mockSelect;

    /** @var MockObject|Sql\Select */
    protected $mockSelectCount;

    /** @var MockObject|StatementInterface */
    protected $mockStatement;

    /** @var MockObject|ResultInterface */
    protected $mockResult;

    /** @var MockObject|Sql\Sql */
    protected $mockSql;

    /** @var Select */
    protected $dbSelect;

    /**
     * @throws Exception
     */
    #![Override]
    public function setUp(): void
    {
        $this->mockResult    = $this->createMock(ResultInterface::class);
        $this->mockStatement = $this->createMock(StatementInterface::class);

        $this->mockStatement->expects($this->any())->method('execute')->willReturn($this->mockResult);

        $mockDriver   = $this->createMock(DriverInterface::class);
        $mockPlatform = $this->createMock(PlatformInterface::class);

        $mockDriver->expects($this->any())->method('createStatement')->willReturn($this->mockStatement);
        $mockPlatform->expects($this->any())->method('getName')->willReturn('platform');

        $this->mockSql = $this->getMockBuilder(Sql\Sql::class)
            ->setConstructorArgs(
                [
                    $this->getMockBuilder(Adapter::class)
                         ->setConstructorArgs([$mockDriver, $mockPlatform])
                         ->getMock(),
                ]
            )->getMock();

        $this->mockSql
            ->expects($this->any())
            ->method('prepareStatementForSqlObject')
            ->with($this->isInstanceOf(Sql\Select::class))
            ->willReturn($this->mockStatement);

        $this->mockSelect      = $this->createMock(Sql\Select::class);
        $this->mockSelectCount = $this->createMock(Sql\Select::class);
        $this->dbSelect        = new Select($this->mockSelect, $this->mockSql);
    }

    public function testGetItems(): void
    {
        $this->mockSelect
            ->expects($this->once())
            ->method('limit')
            ->with($this->equalTo(10))
            ->willReturnSelf();

        $this->mockSelect
            ->expects($this->once())
            ->method('offset')
            ->with($this->equalTo(2))
            ->willReturnSelf();

        $items = $this->dbSelect->getItems(2, 10);
        $this->assertEquals([], $items);
    }

    public function testCount(): void
    {
        $this->mockResult
            ->expects($this->once())
            ->method('current')
            ->willReturn([Select::ROW_COUNT_COLUMN_NAME => 5]);

        $this->mockSelect->expects($this->exactly(3))->method('reset'); // called for columns, limit, offset, order

        $count = $this->dbSelect->count();
        $this->assertEquals(5, $count);
    }

    public function testCountQueryWithLowerColumnNameShouldReturnValidResult(): void
    {
        $this->dbSelect = new Select($this->mockSelect, $this->mockSql);
        $this->mockResult
            ->expects($this->once())
            ->method('current')
            ->willReturn([strtolower(Select::ROW_COUNT_COLUMN_NAME) => 7]);

        $count = $this->dbSelect->count();
        $this->assertEquals(7, $count);
    }

    public function testCountQueryWithMissingColumnNameShouldRaiseException(): void
    {
        $this->dbSelect = new Select($this->mockSelect, $this->mockSql);
        $this->mockResult
            ->expects($this->once())
            ->method('current')
            ->willReturn([]);

        $this->expectException(MissingRowCountColumnException::class);
        $this->dbSelect->count();
    }

    public function testCustomCount(): void
    {
        $this->dbSelect = new Select($this->mockSelect, $this->mockSql, null, $this->mockSelectCount);
        $this->mockResult
            ->expects($this->once())
            ->method('current')
            ->willReturn([Select::ROW_COUNT_COLUMN_NAME => 7]);

        $count = $this->dbSelect->count();
        $this->assertEquals(7, $count);
    }

    /**
     * @group 6817
     * @group 6812
     */
    public function testReturnValueIsArray(): void
    {
        $this->mockSelect
            ->expects($this->once())
            ->method('limit')
            ->with($this->equalTo(10))
            ->willReturnSelf();

        $this->mockSelect
            ->expects($this->once())
            ->method('offset')
            ->with($this->equalTo(0))
            ->willReturnSelf();

        $this->assertIsArray($this->dbSelect->getItems(0, 10));
    }

    public function testGetArrayCopyShouldContainSelectItems(): void
    {
        $this->dbSelect = new Select(
            $this->mockSelect,
            $this->mockSql,
            null,
            $this->mockSelectCount
        );
        $this->assertSame(
            [
                'select',
                'count_select',
            ],
            array_keys($this->dbSelect->getArrayCopy())
        );
    }
}
