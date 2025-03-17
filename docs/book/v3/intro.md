# Introduction

This library provides two adapters for [laminas/laminas-paginator](https://docs.laminas.dev/laminas-paginator):

- `Laminas\Db\Paginator\Adapter\Select`
- `Laminas\Db\Paginator\Adapter\TableGateway`

These provide pagination support for [laminas/laminas-db](https://docs.laminas.dev/laminas-db/) SQL select and TableGateway features.

- [Select documentation](db-select.md)
- [TableGateway documentation](db-table-gateway.md)

Each is configured with the `Laminas\Paginator\AdapterPluginManager` when used in laminas-mvc applications, or in applications using config providers, such as Mezzio applications.
