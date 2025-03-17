<?php

declare(strict_types=1);

namespace Laminas\Db\Paginator\Adapter;

final class ConfigProvider
{
    /**
     * Retrieve default laminas-paginator configuration.
     *
     * @return array
     */
    public function __invoke(): array
    {
        return [
            'paginators' => $this->getPaginatorConfig(),
        ];
    }

    /**
     * Retrieve configuration for laminas-paginator adapter plugin manager.
     *
     * @return array
     */
    public function getPaginatorConfig(): array
    {
        return [
            'aliases'   => [
                'select'       => Select::class,
                'Select'       => Select::class,
                'tablegateway' => TableGateway::class,
                'tableGateway' => TableGateway::class,
                'TableGateway' => TableGateway::class,
            ],
            'factories' => [
                Select::class       => SelectFactory::class,
                TableGateway::class => TableGatewayFactory::class,
            ],
        ];
    }
}
