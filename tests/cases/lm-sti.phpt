<?php

declare(strict_types=1);

use CzProject\SqlSchema;
use Inlm\SchemaGenerator;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$directory = __DIR__ . '/lm-sti';

require $directory . '/Mapper.php';

$adapter = new Test\DummyAdapter(new SchemaGenerator\Configuration(new SqlSchema\Schema));
$extractor = new SchemaGenerator\LeanMapperBridge\LeanMapperExtractor($directory, new Mapper);
$dumper = new SchemaGenerator\Dumpers\SqlMemoryDumper;
$logger = new CzProject\Logger\MemoryLogger;

$schemaGenerator = new SchemaGenerator\SchemaGenerator($extractor, $adapter, $dumper, $logger);
$schemaGenerator->generate();

Assert::matchFile($directory . '/dump-mysql.sql', $dumper->getSql());

Assert::same([
	'Generating schema',
	'Generating diff',
	'Generating migrations',
	' - created table client',
	'Saving schema',
	'Done.',
], $logger->getLog());
