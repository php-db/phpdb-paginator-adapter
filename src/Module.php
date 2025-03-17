<?php

declare(strict_types=1);

namespace Laminas\Db\Paginator\Adapter;

final class Module
{
    /**
     * Retrieve configuration for laminas-paginator adapter plugin manager for laminas-mvc context.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return (new ConfigProvider())();
    }
}
