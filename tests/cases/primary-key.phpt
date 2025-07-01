<?php

declare(strict_types=1);

use CzProject\SqlSchema;
use Inlm\SchemaGenerator\Configuration;
use Inlm\SchemaGenerator\ConfigurationSerializer;
use Inlm\SchemaGenerator\Database;
use Inlm\SchemaGenerator\DataType;
use Inlm\SchemaGenerator\LeanMapperBridge\LeanMapperExtractor;
use Inlm\SchemaGenerator\SchemaGenerator;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$directory = __DIR__ . '/primary-key';

require $directory . '/Mapper.php';
require $directory . '/Book.php';
require $directory . '/BookMeta.php';


test(function () use ($directory) {
	$extractor = new LeanMapperExtractor($directory, new \Test\LeanMapperExtractor\PrimaryKey\Mapper);

	$schema = $extractor->generateSchema([], [], Database::MYSQL);
	$serialized = ConfigurationSerializer::serialize(new Configuration($schema));
	$generated = $serialized['schema'];
	assert(is_array($generated));
	ksort($generated, SORT_STRING);

	Assert::same([
		'book' => [
			'columns' => [
				'id' => [
					'type' => 'INT',
					'parameters' => [10],
					'options' => [SqlSchema\Column::OPTION_UNSIGNED => NULL],
					'autoIncrement' => TRUE,
				],

				'name' => [
					'type' => 'TEXT',
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

		'bookmeta' => [
			'columns' => [
				'book_id' => [
					'type' => 'INT',
					'parameters' => [10],
					'options' => [SqlSchema\Column::OPTION_UNSIGNED => NULL],
				],

				'year' => [
					'type' => 'INT',
					'parameters' => [11],
				],
			],

			'indexes' => [
				'' => [
					'type' => SqlSchema\Index::TYPE_PRIMARY,
					'columns' => [
						[
							'name' => 'book_id',
						],
					],
				]
			],

			'foreignKeys' => [
				'bookmeta_fk_book_id' => [
					'columns' => ['book_id'],
					'targetTable' => 'book',
					'targetColumns' => ['id'],
					'onUpdateAction' => 'RESTRICT',
					'onDeleteAction' => 'RESTRICT',
				],
			],
		],

		'bookmeta2' => [
			'columns' => [
				'book_id' => [
					'type' => 'INT',
					'parameters' => [10],
					'options' => [SqlSchema\Column::OPTION_UNSIGNED => NULL],
				],

				'rating' => [
					'type' => 'INT',
					'parameters' => [11],
				],
			],

			'indexes' => [
				'' => [
					'type' => SqlSchema\Index::TYPE_PRIMARY,
					'columns' => [
						[
							'name' => 'book_id',
						],
					],
				]
			],

			'foreignKeys' => [
				'bookmeta2_fk_book_id' => [
					'columns' => ['book_id'],
					'targetTable' => 'book',
					'targetColumns' => ['id'],
					'onUpdateAction' => 'RESTRICT',
					'onDeleteAction' => 'RESTRICT',
				],
			],
		],
	], $generated);
});
