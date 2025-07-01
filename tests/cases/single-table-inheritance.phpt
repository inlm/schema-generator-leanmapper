<?php

declare(strict_types=1);

use CzProject\SqlSchema;
use Inlm\SchemaGenerator\Configuration;
use Inlm\SchemaGenerator\ConfigurationSerializer;
use Inlm\SchemaGenerator\LeanMapperBridge\LeanMapperExtractor;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$directory = __DIR__ . '/single-table-inheritance';

require $directory . '/User.php';
require $directory . '/UserIndividual.php';
require $directory . '/UserCompany.php';
require $directory . '/Mapper.php';


test(function () use ($directory) {
	$extractor = new LeanMapperExtractor($directory, new Test\LeanMapperExtractor\SingleTableInheritance\Mapper);

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
