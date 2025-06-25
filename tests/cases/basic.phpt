<?php

declare(strict_types=1);

use Inlm\SchemaGenerator\Configuration;
use Inlm\SchemaGenerator\ConfigurationSerializer;
use Inlm\SchemaGenerator\LeanMapperBridge\LeanMapperExtractor;
use Nette\Neon\Neon;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/basic/Person.php';
require __DIR__ . '/basic/Author.php';
require __DIR__ . '/basic/Book.php';
require __DIR__ . '/basic/Tag.php';


test(function () {
	$extractor = new LeanMapperExtractor(__DIR__ . '/basic', new \LeanMapper\DefaultMapper);

	$schema = $extractor->generateSchema();
	$serialized = ConfigurationSerializer::serialize(new Configuration($schema));
	$generated = $serialized['schema'];
	assert(is_array($generated));
	ksort($generated, SORT_STRING);

	$expected = Test\Schema::createArray();
	Assert::same($expected, $generated);
});
