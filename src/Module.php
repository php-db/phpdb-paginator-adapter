<?php

declare(strict_types=1);

namespace Laminas\Db\Paginator\Adapter;

class Module
{
    /**
     * Return default laminas-db-paginator-adapter configuration.
     *
     * @return array[]
     */
    public function getConfig(): array
    {
        return [
            'paginators' => [
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
            ],
        ];
    }
}
