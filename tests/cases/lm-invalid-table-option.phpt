<?php

declare(strict_types=1);

use CzProject\SqlSchema;
use Inlm\SchemaGenerator;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$directory = __DIR__ . '/lm-invalid-table-option';
$adapter = new Test\DummyAdapter(new SchemaGenerator\Configuration(new SqlSchema\Schema));
$extractor = new SchemaGenerator\LeanMapperBridge\LeanMapperExtractor($directory, new LeanMapper\DefaultMapper);
$dumper = new SchemaGenerator\Dumpers\SqlMemoryDumper;
$logger = new CzProject\Logger\MemoryLogger;

$schemaGenerator = new SchemaGenerator\SchemaGenerator($extractor, $adapter, $dumper, $logger);

Assert::exception(function () use ($schemaGenerator) {
	$schemaGenerator->generate();
}, Inlm\SchemaGenerator\LeanMapperBridge\EmptyException::class, "Empty definition of '@schemaOption'.");
