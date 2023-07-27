<?php

	namespace Inlm\SchemaGenerator\LeanMapperBridge;

	use Inlm\SchemaGenerator\DataType;
	use Inlm\SchemaGenerator\IExtractor;
	use Inlm\SchemaGenerator\Utils\Generator;
	use Inlm\SchemaGenerator\Utils\DataTypeParser;
	use Inlm\SchemaGenerator\Utils\DataTypeProcessor;
	use LeanMapper\IMapper;
	use LeanMapper\Reflection;
	use LeanMapper\Reflection\AnnotationsParser;
	use LeanMapper\Relationship;
	use Nette\Utils\Validators;


	class LeanMapperExtractor implements IExtractor
	{
		/** @var IMapper */
		protected $mapper;

		/** @var string|string[] */
		protected $directories;

		/** @var array<string, DataType> */
		protected $customTypes;

		/** @var string|NULL */
		protected $databaseType;


		/**
		 * @param  string|string[] $directories
		 */
		public function __construct($directories, IMapper $mapper)
		{
			$this->directories = $directories;
			$this->mapper = $mapper;
		}


		public function generateSchema(array $options = [], array $customTypes = [], $databaseType = NULL)
		{
			$generator = new Generator($options, $databaseType);
			$this->customTypes = $customTypes;
			$this->databaseType = $databaseType;
			$entities = $this->findEntities();

			foreach ($entities as $entity) {
				$this->generateEntity($entity, $generator);
			}

			$schema = $generator->finalize();
			$generator = NULL;
			return $schema;
		}


		/**
		 * @param  class-string $entityClass
		 * @return void
		 */
		protected function generateEntity($entityClass, Generator $generator)
		{
			$reflectionFactory = [$entityClass, 'getReflection'];

			if (!is_callable($reflectionFactory)) {
				return;
			}

			$reflection = call_user_func($reflectionFactory, $this->mapper);
			$properties = $reflection->getEntityProperties();

			if (empty($properties) || $this->isEntityIgnored($reflection->getDocComment())) {
				return;
			}

			$tableName = $this->mapper->getTable($entityClass);
			$tablePrimaryColumn = $this->mapper->getPrimaryKey($tableName);
			$generator->createTable($tableName, $tablePrimaryColumn);

			foreach ($this->getFamilyLine($reflection) as $member) {
				$docComment = $member->getDocComment();

				if ($docComment === FALSE) {
					continue;
				}

				$this->extractTableComment($tableName, $docComment, $generator);
				$this->extractTableOption($tableName, $docComment, $generator);
				$this->extractTableIndexes($tableName, $member, 'primary', $entityClass, $generator);
				$this->extractTableIndexes($tableName, $member, 'unique', $entityClass, $generator);
				$this->extractTableIndexes($tableName, $member, 'index', $entityClass, $generator);
			}

			// hack - primary column must be always first (@property-read is always last)
			if (isset($properties[$tablePrimaryColumn])) {
				$properties = [$tablePrimaryColumn => $properties[$tablePrimaryColumn]] + $properties;
			}

			foreach ($properties as $property) {
				if ($property->hasRelationship()) {
					$relationship = $property->getRelationship();

					if ($relationship instanceof Relationship\HasMany) {
						$this->addHasManyRelationship($relationship, $tableName, $generator);
						continue; // virtual column

					} elseif ($relationship instanceof Relationship\HasOne) {
						$relationshipColumnReferencingTargetTable = $relationship->getColumnReferencingTargetTable();
						$relationshipTargetTable = $relationship->getTargetTable();

						if ($relationshipColumnReferencingTargetTable === NULL) {
							throw new InvalidStateException('Missing relationship columnReferencingTargetTables.');
						}

						if ($relationshipTargetTable === NULL) {
							throw new InvalidStateException('Missing relationship targetTable.');
						}

						$generator->addRelationship($tableName, $relationshipColumnReferencingTargetTable, $relationshipTargetTable);

					} elseif ($relationship instanceof Relationship\BelongsTo) {
						$relationshipColumnReferencingSourceTable = $relationship->getColumnReferencingSourceTable();
						$relationshipTargetTable = $relationship->getTargetTable();

						if ($relationshipColumnReferencingSourceTable === NULL) {
							throw new InvalidStateException('Missing relationship columnReferencingSourceTable.');
						}

						if ($relationshipTargetTable === NULL) {
							throw new InvalidStateException('Missing relationship targetTable.');
						}

						$generator->addRelationship($relationshipTargetTable, $relationshipColumnReferencingSourceTable, $tableName);
						continue; // virtual column

					} else {
						throw new \RuntimeException('Unknow relationship ' . (is_object($relationship) ? get_class($relationship) : gettype($relationship))); // TODO
					}
				}

				$propertyName = $property->getName();
				$columnName = $property->getColumn();
				$isPrimaryColumn = $columnName === $tablePrimaryColumn;
				$columnType = NULL;

				if (!$property->hasRelationship()) {
					$columnType = $this->extractColumnType($property, $isPrimaryColumn, $entityClass);
				}

				$columnDefaultValue = $this->extractColumnDefaultValue($tableName, $columnName, $property);

				$generator->addColumn($tableName, $columnName, $columnType, $columnDefaultValue);
				$generator->setColumnNullable($tableName, $columnName, $property->isNullable());

				$this->extractColumnComment($tableName, $columnName, $property, $generator);
				$this->extractColumnAutoIncrement($tableName, $columnName, $property, $isPrimaryColumn, $generator);
				$this->extractColumnIndex($property, 'primary', $tableName, $columnName, $generator);
				$this->extractColumnIndex($property, 'unique', $tableName, $columnName, $generator);
				$this->extractColumnIndex($property, 'index', $tableName, $columnName, $generator);
			}
		}


		/**
		 * @param  string $docComment
		 * @return bool
		 */
		protected function isEntityIgnored($docComment)
		{
			// @schema-ignore
			// @schemaIgnore
			$annotations = [
				'schema-ignore',
				'schemaIgnore',
			];

			foreach ($annotations as $annotation) {
				if (AnnotationsParser::parseSimpleAnnotationValue($annotation, $docComment) !== NULL) {
					return TRUE;
				}
			}

			return FALSE;
		}


		/**
		 * @param  string $tableName
		 * @param  string $docComment
		 * @return void
		 */
		protected function extractTableComment($tableName, $docComment, Generator $generator)
		{
			// @schema-comment comment
			// @schemaComment comment
			$annotations = [
				'schema-comment',
				'schemaComment',
			];

			foreach ($annotations as $annotation) {
				foreach (AnnotationsParser::parseAnnotationValues($annotation, $docComment) as $comment) {
					$generator->setTableComment($tableName, $comment);
				}
			}
		}


		/**
		 * @param  string $tableName
		 * @param  string $docComment
		 * @return void
		 */
		protected function extractTableOption($tableName, $docComment, Generator $generator)
		{
			// @schema-option option value
			// @schemaOption option value
			$annotations = [
				'schema-option',
				'schemaOption',
			];

			foreach ($annotations as $annotation) {
				foreach (AnnotationsParser::parseAnnotationValues($annotation, $docComment) as $definition) {
					$definition = trim($definition);

					if ($definition === '*/') { // fix for bug in AnnotationsParser::parseAnnotationValues
						$definition = '';
					}

					if ($definition === '') {
						throw new EmptyException("Empty definition of '@{$annotation}'.");
					}

					$option = $definition;
					$value = NULL;

					if (strpos($definition, ' ') !== FALSE) {
						list($option, $value) = explode(' ', $definition, 2);
						$option = trim($option);
						$value = trim($value);
					}

					if ($option === '') {
						throw new MissingException("Missing option name in '@{$annotation}'.");
					}

					if ($value === NULL) {
						throw new MissingException("Missing option value in '@{$annotation}'.");
					}

					$generator->setTableOption($tableName, $option, $value);
				}
			}
		}


		/**
		 * @param  string $tableName
		 * @param  string $indexType
		 * @param  string $entityClass
		 * @return void
		 */
		protected function extractTableIndexes($tableName, Reflection\EntityReflection $reflection, $indexType, $entityClass, Generator $generator)
		{
			// @schema-<type> property1, property2
			// @schema<Type> property, property2
			$annotations = [
				'schema-' . $indexType,
				'schema' . ucfirst($indexType),
			];

			foreach ($annotations as $annotation) {
				foreach (AnnotationsParser::parseAnnotationValues($annotation, (string) $reflection->getDocComment()) as $definition) {
					$properties = array_map('trim', explode(',', $definition));
					$columns = [];

					foreach ($properties as $propertyName) {
						$property = $reflection->getEntityProperty($propertyName);

						if ($property !== NULL) {
							$column = $property->getColumn();

							if ($column === NULL) {
								throw new MissingException("Missing column name for property '$propertyName'.");
							}

							$columns[] = $column;

						} else { // fallback
							$columns[] = $this->mapper->getColumn($entityClass, $propertyName);
						}
					}

					$this->addIndexByType($indexType, $tableName, $columns, $generator);
				}
			}
		}


		/**
		 * @param  bool $isPrimaryColumn
		 * @param  string $entityClass
		 * @return DataType
		 */
		protected function extractColumnType(Reflection\Property $property, $isPrimaryColumn, $entityClass)
		{
			$datatype = NULL;

			if ($property->hasCustomFlag('schema-type')) {
				$datatype = $this->parseTypeFlag($property, 'schema-type');

			} elseif ($property->hasCustomFlag('schemaType')) {
				$datatype = $this->parseTypeFlag($property, 'schemaType');
			}

			try {
				return DataTypeProcessor::process($property->getType(), $datatype, $isPrimaryColumn, $this->customTypes, $this->databaseType);

			} catch (MissingException $e) {
				throw new MissingException("Missing type for property '{$property->getName()}' in entity '{$entityClass}'.", 0, $e);
			}
		}


		/**
		 * @param  string $flagName
		 * @return DataType
		 * @throws InvalidArgumentException
		 */
		protected function parseTypeFlag(Reflection\Property $property, $flagName)
		{
			$s = $property->getCustomFlagValue($flagName);
			try {
				return DataTypeParser::parse($s, DataTypeParser::SYNTAX_ALTERNATIVE);

			} catch (\Exception $e) { // TODO
				throw new InvalidArgumentException("Malformed m:{$flagName} definition for property '{$property->getName()}' in entity ''.");
			}
		}


		/**
		 * @param  string $tableName
		 * @param  string $columnName
		 * @return void
		 */
		protected function extractColumnComment($tableName, $columnName, Reflection\Property $property, Generator $generator)
		{
			if ($property->hasCustomFlag('schema-comment')) {
				$generator->setColumnComment($tableName, $columnName, $property->getCustomFlagValue('schema-comment'));

			} elseif ($property->hasCustomFlag('schemaComment')) {
				$generator->setColumnComment($tableName, $columnName, $property->getCustomFlagValue('schemaComment'));
			}
		}


		/**
		 * @param  string $tableName
		 * @param  string $columnName
		 * @return scalar|NULL
		 */
		protected function extractColumnDefaultValue($tableName, $columnName, Reflection\Property $property)
		{
			$value = NULL;

			if ($property->hasCustomFlag('schema-default')) {
				$value = $property->getCustomFlagValue('schema-default');

			} elseif ($property->hasCustomFlag('schemaDefault')) {
				$value = $property->getCustomFlagValue('schemaDefault');
			}

			if ($value !== NULL) {
				if (Validators::isNumericInt($value)) {
					$value = (int) $value;

				} elseif (Validators::isNumeric($value)) {
					$value = (float) $value;
				}
			}

			return $value;
		}


		/**
		 * @param  string $tableName
		 * @param  string $columnName
		 * @param  bool $isPrimaryColumn
		 * @return void
		 */
		protected function extractColumnAutoIncrement($tableName, $columnName, Reflection\Property $property, $isPrimaryColumn, Generator $generator)
		{
			if ($property->hasCustomFlag('schema-autoIncrement') || $property->hasCustomFlag('schemaAutoIncrement')) {
				$generator->setColumnAutoIncrement($tableName, $columnName, TRUE);

			} else { // auto-detect
				$generator->setColumnAutoIncrement($tableName, $columnName, $isPrimaryColumn && $property->getType() === 'integer');
			}
		}


		/**
		 * @param  string $type
		 * @param  string $tableName
		 * @param  string $columnName
		 * @return void
		 */
		protected function extractColumnIndex(Reflection\Property $property, $type, $tableName, $columnName, Generator $generator)
		{
			$flags = [
				'schema-' . $type,
				'schema' . ucfirst($type),
			];

			foreach ($flags as $flag) {
				if ($property->hasCustomFlag($flag)) {
					$this->addIndexByType($type, $tableName, $columnName, $generator);
					return;
				}
			}
		}


		/**
		 * @param  string $indexType
		 * @param  string $tableName
		 * @param  string|string[] $columns
		 * @return void
		 */
		protected function addIndexByType($indexType, $tableName, $columns, Generator $generator)
		{
			if ($indexType === 'index') {
				$generator->addIndex($tableName, $columns);

			} elseif ($indexType === 'unique') {
				$generator->addUniqueIndex($tableName, $columns);

			} elseif ($indexType === 'primary') {
				$generator->addPrimaryIndex($tableName, $columns);

			} else {
				throw new InvalidArgumentException("Unknow index type '$indexType'.");
			}
		}


		/**
		 * @param  string $sourceTable
		 * @return void
		 */
		protected function addHasManyRelationship(Relationship\HasMany $relationship, $sourceTable, Generator $generator)
		{
			$relationshipTable = $relationship->getRelationshipTable();
			$sourceColumn = $relationship->getColumnReferencingSourceTable();
			$targetTable = $relationship->getTargetTable();
			$targetColumn = $relationship->getColumnReferencingTargetTable();

			if ($relationshipTable === NULL) {
				throw new InvalidStateException('Missing relationshipTable.');
			}

			if ($sourceColumn === NULL) {
				throw new InvalidStateException('Missing sourceColumn.');
			}

			if ($targetTable === NULL) {
				throw new InvalidStateException('Missing targetTable.');
			}

			if ($targetColumn === NULL) {
				throw new InvalidStateException('Missing targetColumn.');
			}

			if ($this->mapper->getRelationshipTable($sourceTable, $targetTable) === $relationshipTable) {
				$generator->addHasManyTable(
					$relationshipTable,
					$sourceTable,
					$sourceColumn,
					$targetTable,
					$targetColumn
				);

			} else {
				$generator->addHasManyTable(
					$relationshipTable,
					$targetTable,
					$targetColumn,
					$sourceTable,
					$sourceColumn
				);
			}
		}


		/**
		 * Returns list of FQN class names.
		 * @return class-string[]
		 */
		protected function findEntities()
		{
			$directories = is_array($this->directories) ? $this->directories : [$this->directories];
			$phpClassFinder = new \Inlm\SchemaGenerator\Utils\PhpClassFinder($directories);
			$classes = $phpClassFinder->find();
			$entities = [];

			foreach ($classes->getClasses() as $class) {
				$accept = !$class->isAbstract()
					&& $classes->isSubclassOf($class, \LeanMapper\Entity::class);

				$entityClass = $class->getName();

				if ($accept && !class_exists($entityClass)) {
					$class->loadFile();
				}

				if ($accept && class_exists($entityClass, FALSE)) {
					$entities[] = $entityClass;
				}
			}

			sort($entities, SORT_STRING);
			return $entities;
		}


		/**
		 * @return Reflection\EntityReflection[]
		 */
		protected function getFamilyLine(Reflection\EntityReflection $reflection)
		{
			$line = [$member = $reflection];

			while ($member = $member->getParentClass()) {
				if ($member->name === \LeanMapper\Entity::class) {
					break;
				}

				$line[] = $member;
			}

			return array_reverse($line);
		}
	}
