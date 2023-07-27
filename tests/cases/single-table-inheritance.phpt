<?php

use CzProject\SqlSchema;
use Inlm\SchemaGenerator\Configuration;
use Inlm\SchemaGenerator\ConfigurationSerializer;
use Inlm\SchemaGenerator\LeanMapperBridge\LeanMapperExtractor;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
$baseDir = require __DIR__ . '/single-table-inheritance/loader.php';


test(function () use ($baseDir) {
	$extractor = new LeanMapperExtractor($baseDir, new Test\LeanMapperExtractor\SingleTableInheritance\Mapper);

	$schema = $extractor->generateSchema();
	$serialized = ConfigurationSerializer::serialize(new Configuration($schema));
	$generated = $serialized['schema'];
	assert(is_array($generated));
	ksort($generated, SORT_STRING);

	Assert::same([
		'user' => [
			'columns' => [
				'id' => [
					'type' => 'INT',
					'options' => [SqlSchema\Column::OPTION_UNSIGNED => NULL],
					'autoIncrement' => TRUE,
				],

				'type' => [
					'type' => 'TINYINT',
					'options' => [SqlSchema\Column::OPTION_UNSIGNED => NULL],
				],

				'created' => [
					'type' => 'DATETIME',
				],

				'updated' => [
					'type' => 'DATETIME',
					'nullable' => TRUE,
				],

				'companyName' => [
					'type' => 'VARCHAR',
					'parameters' => [200],
					'nullable' => TRUE,
				],

				'ico' => [
					'type' => 'VARCHAR',
					'parameters' => [8],
					'nullable' => TRUE,
				],

				'note' => [
					'type' => 'VARCHAR',
					'parameters' => [100],
				],

				'firstName' => [
					'type' => 'VARCHAR',
					'parameters' => [100],
					'nullable' => TRUE,
				],

				'lastName' => [
					'type' => 'VARCHAR',
					'parameters' => [100],
					'nullable' => TRUE,
				],
			],

			'indexes' => [
				'' => [
					'type' => SqlSchema\Index::TYPE_PRIMARY,
					'columns' => [
						[
							'name' => 'id',
						],
					],
				]
			]
		],
	], $generated);
});
