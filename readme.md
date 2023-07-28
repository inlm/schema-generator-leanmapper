# Schema Generator

[![Build Status](https://github.com/inlm/schema-generator-leanmapper/workflows/Build/badge.svg)](https://github.com/inlm/schema-generator-leanmapper/actions)
[![Downloads this Month](https://img.shields.io/packagist/dm/inlm/schema-generator-leanmapper.svg)](https://packagist.org/packages/inlm/schema-generator-leanmapper)
[![Latest Stable Version](https://poser.pugx.org/inlm/schema-generator-leanmapper/v/stable)](https://github.com/inlm/schema-generator-leanmapper/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/inlm/schema-generator-leanmapper/blob/master/license.md)

<a href="https://www.janpecha.cz/donate/schema-generator/"><img src="https://buymecoffee.intm.org/img/donate-banner.v1.svg" alt="Donate" height="100"></a>


## Installation

[Download a latest package](https://github.com/inlm/schema-generator-leanmapper/releases) or use [Composer](http://getcomposer.org/):

```
composer require inlm/schema-generator-leanmapper
```

Schema Generator requires PHP 5.6.0 or later.


## Usage

```php
$extractor = new Inlm\SchemaGenerator\LeanMapperBridge\LeanMapperExtractor(__DIR__ . '/model/Entities/', new LeanMapper\DefaultMapper);
$adapter = new Inlm\SchemaGenerator\Adapters\NeonAdapter(__DIR__ . '/.schema.neon');
$dumper = new Inlm\SchemaGenerator\Dumpers\SqlDumper(__DIR__ . '/migrations/structures/');
$logger = new Inlm\SchemaGenerator\Loggers\MemoryLogger;

$generator = new Inlm\SchemaGenerator\SchemaGenerator($extractor, $adapter, $dumper, $logger, Inlm\SchemaGenerator\Database::MYSQL);
// $generator->setTestMode();

$generator->generate();
// or
$generator->generate('changes description');
```

## Documentation

`LeanMapperExtractor` generates schema from [Lean Mapper](http://leanmapper.com/) entities.

```php
$directories = '/path/to/model/Entities/';
// or
$directories = [
	'/path/to/model/Entities/',
	'/path/to/package/Entities/',
];

$mapper = new LeanMapper\DefaultMapper;
$extractor = new Inlm\SchemaGenerator\Extractors\LeanMapperExtractor($directories, $mapper);
```

## Flags

```
@property string|NULL $web m:schemaType(varchar:50)
```

| Flag                    | Description                    | Example                                  |
| ----------------------- | ------------------------------ | ---------------------------------------- |
| `m:schemaType`          | column datatype                | `m:schemaType(varchar:50)`, `m:schemaType(int:10 unsigned)` |
| `m:schemaComment`       | column comment                 | `m:schemaComment(Lorem ipsum)`           |
| `m:schemaAutoIncrement` | has column AUTO_INCREMENT?     | `m:schemaAutoIncrement`                  |
| `m:schemaIndex`         | create INDEX for column        | `m:schemaIndex`                          |
| `m:schemaPrimary`       | create PRIMARY KEY for column  | `m:schemaPrimary`                        |
| `m:schemaUnique`        | create UNIQUE INDEX for column | `m:schemaUnique`                         |
| `m:schemaIgnore`        | ignore property                | `m:schemaUnique`                         |

If primary column is `integer` (`@property int $id`), automatically gets `AUTO_INCREMENT`.

Flag `m:schemaType` can be used with [custom types](https://github.com/inlm/schema-generator/blob/master/docs/custom-types.md) too - for example `m:schemaType(money)` or `m:schemaType(money unsigned)`.

In case if is flag `m:schemaType` missing, it uses [default type](https://github.com/inlm/schema-generator/blob/master/docs/default-types.md) or your [custom type](https://github.com/inlm/schema-generator/blob/master/docs/custom-types.md).


## Annotations

| Annotation       | Description         | Example                               |
| ---------------- | ------------------- | ------------------------------------- |
| `@schemaComment` | table comment       | `@schemaComment Lorem ipsum`          |
| `@schemaOption`  | table option        | `@schemaOption COLLATE utf8_czech_ci` |
| `@schemaIndex`   | create INDEX        | `@schemaIndex propertyA, propertyB`   |
| `@schemaPrimary` | create PRIMARY KEY  | `@schemaPrimary propertyA, propertyB` |
| `@schemaUnique`  | create UNIQUE INDEX | `@schemaUnique propertyA, propertyB`  |
| `@schemaIgnore`  | ignore entity       | `@schemaIgnore`                       |

You can define default [table options](https://github.com/inlm/schema-generator/blob/master/docs/table-options.md) globally.


## Example

```php
/**
 * @property int $id
 * @property string $name m:schemaType(varchar:100)
 * @schemaOption COLLATE utf8_czech_ci
 */
class Author extends \LeanMapper\Entity
{
}
```


------------------------------

License: [New BSD License](license.md)
<br>Author: Jan Pecha, https://www.janpecha.cz/
