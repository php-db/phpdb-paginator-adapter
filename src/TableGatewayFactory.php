<?php

declare(strict_types=1);

namespace Laminas\Db\Paginator\Adapter;

use Laminas\Db\Paginator\Adapter\Exception\InvalidArgumentException;
use Laminas\Db\Paginator\Adapter\Exception\UnexpectedValueException;
use Psr\Container\ContainerInterface;
use ReflectionException;

class TableGatewayFactory extends AbstractAdapterFactory
{
    /**
     * @param array|null         $options
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     * @throws ReflectionException
     */
    public function __invoke(ContainerInterface $container, string $requestedName, ?array $options = null): Select
    {
        return $this->buildAdapter(TableGateway::class, $requestedName, $options);
    }
}
