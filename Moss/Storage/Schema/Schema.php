<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema as SchemaAsset;
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Model\ModelInterface;

/**
 * Schema used to create and execute table related operations (create, alter, drop)
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class Schema implements SchemaInterface
{
    const OPERATION_CREATE = 'create';
    const OPERATION_ALTER = 'alter';
    const OPERATION_DROP = 'drop';

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ModelBag
     */
    protected $models;

    /**
     * @var SchemaAsset;
     */
    protected $schema;

    protected $operation;

    protected $queries = [];

    /**
     * Constructor
     *
     * @param Connection $connection
     * @param ModelBag   $models
     */
    public function __construct(Connection $connection, ModelBag $models)
    {
        $this->connection = $connection;
        $this->models = $models;
    }

    /**
     * Returns connection
     *
     * @return Connection
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * Sets create operation
     *
     * @param array|string $entity
     *
     * @return $this
     */
    public function create($entity = [])
    {
        return $this->operation(self::OPERATION_CREATE, $entity);
    }

    /**
     * Sets alter operation
     *
     * @param array|string $entity
     *
     * @return $this
     */
    public function alter($entity = [])
    {
        return $this->operation(self::OPERATION_ALTER, $entity);
    }

    /**
     * Sets drop operation
     *
     * @param array|string $entity
     *
     * @return $this
     */
    public function drop($entity = [])
    {
        return $this->operation(self::OPERATION_DROP, $entity);
    }

    /**
     * Sets query operation
     *
     * @param string       $operation
     * @param string|array $entity
     *
     * @return $this
     * @throws SchemaException
     */
    public function operation($operation, $entity = [])
    {
        $this->operation = $operation;

        $this->schema = $this->connection->getSchemaManager()
            ->createSchema();

        $models = $this->retrieveModels($entity);

        switch ($this->operation) {
            case self::OPERATION_CREATE:
                $this->buildCreate($models);
                break;
            case self::OPERATION_ALTER:
                $this->buildAlter($models);
                break;
            case self::OPERATION_DROP:
                $this->buildDrop($models);
                break;
            default:
                throw new SchemaException(sprintf('Unknown operation "%s" in schema query', $this->operation));
        }

        return $this;
    }

    /**
     * Returns array with models for operation
     *
     * @param string|array $entity
     *
     * @return array|ModelInterface[]
     */
    protected function retrieveModels($entity = [])
    {
        $models = [];
        foreach ((array) $entity as $node) {
            $models[] = $this->models->get($node);
        }

        if (empty($models)) {
            $models = $this->models->all();
        }

        return $models;
    }

    /**
     * Builds create table queries
     *
     * @param array|ModelInterface[] $models
     *
     * @throws SchemaException
     */
    protected function buildCreate(array $models)
    {
        $schemaManager = $this->connection->getSchemaManager();

        foreach ($models as $model) {
            if ($schemaManager->tablesExist($model->table())) {
                throw new SchemaException(sprintf('Unable to create table, table "%s" already exists', $model->table()));
            }

            $this->createTable($this->schema, $model);
        }

        $this->queries = array_merge(
            $this->queries,
            $this->schema->toSql($this->connection->getDatabasePlatform())
        );
    }

    /**
     * Builds table alteration queries
     *
     * @param array|ModelInterface[] $models
     */
    protected function buildAlter(array $models)
    {
        $schemaManager = $this->connection->getSchemaManager();
        $fromSchema = $schemaManager->createSchema();
        $toSchema = clone $fromSchema;

        foreach ($models as $model) {
            if($toSchema->hasTable($model->table())) {
                $toSchema->dropTable($model->table());
            }

            $this->createTable($toSchema, $model);
        }

        $sql = $fromSchema->getMigrateToSql($toSchema, $this->connection->getDatabasePlatform());

        $this->queries = array_merge($this->queries, $sql);
    }

    /**
     * Creates table from model into schema
     *
     * @param SchemaAsset    $schema
     * @param ModelInterface $model
     */
    protected function createTable(SchemaAsset $schema, ModelInterface $model)
    {
        $table = $schema->createTable($this->quoteIdentifier($model->table()));

        foreach ($model->fields() as $field) {
            $table->addColumn(
                $this->quoteIdentifier($field->mappedName()),
                $field->type(),
                $field->attributes()
            );
        }

        foreach ($model->indexes() as $index) {
            switch ($index->type()) {
                case 'primary':
                    $table->setPrimaryKey(
                        $this->quoteIdentifier($index->fields()),
                        $this->quoteIdentifier($index->name())
                    );
                    break;
                case 'unique':
                    $table->addUniqueIndex(
                        $this->quoteIdentifier($index->fields()),
                        $this->quoteIdentifier($index->name())
                    );
                    break;
                case 'foreign':
                    $table->addForeignKeyConstraint(
                        $index->table(),
                        $this->quoteIdentifier(array_keys($index->fields())),
                        $this->quoteIdentifier(array_values($index->fields())),
                        ['onUpdate' => 'CASCADE', 'onDelete' => 'RESTRICT'],
                        $this->quoteIdentifier($index->name())
                    );
                    break;
                case 'index':
                default:
                    $table->addIndex(
                        $this->quoteIdentifier($index->fields()),
                        $this->quoteIdentifier($index->name())
                    );
            }
        }
    }

    /**
     * Quotes SQL identifier or array of identifiers
     *
     * @param string|array $identifier
     *
     * @return string|array
     */
    protected function quoteIdentifier($identifier)
    {
        if (!is_array($identifier)) {
            return $this->connection->quoteIdentifier($identifier);
        }

        foreach ($identifier as &$value) {
            $value = $this->connection->quoteIdentifier($value);
            unset($value);
        }

        return $identifier;
    }

    /**
     * Builds drop table query
     *
     * @param array|ModelInterface[] $models
     */
    protected function buildDrop(array $models)
    {
        $schemaManager = $this->connection->getSchemaManager();
        $fromSchema = $schemaManager->createSchema();
        $toSchema = clone $fromSchema;

        foreach ($models as $model) {
            if (!$toSchema->hasTable($model->table())) {
                continue;
            }

            $toSchema->dropTable($model->table());
        }

        $sql = $fromSchema->getMigrateToSql($toSchema, $this->connection->getDatabasePlatform());

        $this->queries = array_merge($this->queries, $sql);
    }

    /**
     * Executes query
     * After execution query is reset
     *
     * @return mixed|null|void
     */
    public function execute()
    {
        $result = [];
        switch ($this->operation) {
            case 'create':
            case 'alter':
            case 'drop':
                foreach ($this->queryString() as $query) {
                    $stmt = $this->connection->prepare($query);
                    $stmt->execute();

                    $result[] = $query;
                }
                break;
            default:
                $result = [];
        }

        $this->reset();

        return $result;
    }

    /**
     * Returns current query string
     *
     * @return array
     */
    public function queryString()
    {
        return $this->queries;
    }

    /**
     * Resets adapter
     *
     * @return $this
     */
    public function reset()
    {
        $this->operation = null;
        $this->schema = null;
        $this->queries = [];

        return $this;
    }
}
