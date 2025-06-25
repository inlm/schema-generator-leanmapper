<?php

	declare(strict_types=1);

	namespace Inlm\SchemaGenerator\LeanMapperBridge;

	use CzProject;
	use Inlm\SchemaGenerator\DibiBridge;
	use Inlm\SchemaGenerator;
	use LeanMapper;


	class LeanMapperIntegration extends SchemaGenerator\Integrations\AbstractIntegration
	{
		/** @var string */
		private $schemaFile;

		/** @var string */
		private $migrationsDirectory;

		/** @var string|string[] */
		private $entityDirectories;

		/** @var array<string, string>|NULL */
		private $options;

		/** @var array<string, string> */
		private $customTypes;

		/** @var string[] */
		private $ignoredTables;

		/** @var string */
		private $databaseType;

		/** @var LeanMapper\Connection */
		private $connection;

		/** @var LeanMapper\IMapper */
		private $mapper;


		/**
		 * @param  string $schemaFile
		 * @param  string $migrationsDirectory
		 * @param  string|string[] $entityDirectories
		 * @param  array<string, string>|NULL $options
		 * @param  array<string, string> $customTypes
		 * @param  string[] $ignoredTables
		 * @param  string|NULL $databaseType
		 */
		public function __construct(
			$schemaFile,
			$migrationsDirectory,
			$entityDirectories,
			array $options = NULL,
			array $customTypes,
			array $ignoredTables,
			$databaseType,
			LeanMapper\Connection $connection,
			LeanMapper\IMapper $mapper
		)
		{
			$this->schemaFile = $schemaFile;
			$this->migrationsDirectory = $migrationsDirectory;
			$this->entityDirectories = $entityDirectories;
			$this->options = $options;
			$this->customTypes = $customTypes;
			$this->ignoredTables = $ignoredTables;
			$this->databaseType = $databaseType !== NULL ? $databaseType : DibiBridge\Dibi::detectDatabaseType($connection);
			$this->connection = $connection;
			$this->mapper = $mapper;
		}


		protected function getOptions()
		{
			return $this->options;
		}


		protected function getCustomTypes()
		{
			return $this->customTypes;
		}


		protected function getDatabaseType()
		{
			return $this->databaseType;
		}


		protected function createExtractor()
		{
			return new LeanMapperExtractor($this->entityDirectories, $this->mapper);
		}


		protected function createAdapter()
		{
			return new SchemaGenerator\Adapters\NeonAdapter($this->schemaFile);
		}


		protected function createDatabaseExtractor()
		{
			return new DibiBridge\DibiExtractor($this->connection, $this->ignoredTables);
		}


		protected function createDatabaseAdapter()
		{
			return new DibiBridge\DibiAdapter($this->connection, $this->ignoredTables);
		}


		protected function createDatabaseDumper()
		{
			return new DibiBridge\DibiDumper($this->connection);
		}


		protected function createSqlDumper()
		{
			$dumper = new SchemaGenerator\Dumpers\SqlDumper($this->migrationsDirectory);
			$dumper->setOutputStructure($dumper::YEAR_MONTH);
			return $dumper;
		}


		protected function createLogger()
		{
			return new CzProject\Logger\OutputLogger;
		}
	}
