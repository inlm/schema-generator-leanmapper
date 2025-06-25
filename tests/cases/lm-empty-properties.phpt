<?php

declare(strict_types=1);

use CzProject\SqlSchema;
use Inlm\SchemaGenerator;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
$baseDir = require __DIR__ . '/lm-empty-properties/loader.php';

$directory = __DIR__ . '/lm-empty-properties';
$adapter = new Test\DummyAdapter(new SchemaGenerator\Configuration(new SqlSchema\Schema));
$extractor = new SchemaGenerator\LeanMapperBridge\LeanMapperExtractor($baseDir, new LeanMapper\DefaultMapper);
$dumper = new SchemaGenerator\Dumpers\SqlMemoryDumper;
$logger = new CzProject\Logger\MemoryLogger;

$schemaGenerator = new SchemaGenerator\SchemaGenerator($extractor, $adapter, $dumper, $logger);
$schemaGenerator->generate();

Assert::same('', $dumper->getSql());

Assert::same([
	'Generating schema',
	'Generating diff',
	'Generating migrations',
	'Saving schema',
	'Done.',
], $logger->getLog());
