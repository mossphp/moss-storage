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

        $this->createCurrentSchema();
    }

    /**
     * Creates instance with current schema
     */
    protected function createCurrentSchema()
    {
        $this->schema = $this->connection->getSchemaManager()->createSchema();
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
     * @param array $entityName
     *
     * @return $this
     */
    public function create(array $entityName = [])
    {
        $this->buildCreate($this->retrieveModels($entityName));

        return $this;
    }

    /**
     * Sets alter operation
     *
     * @param array $entityName
     *
     * @return $this
     */
    public function alter(array $entityName = [])
    {
        $this->buildAlter($this->retrieveModels($entityName));

        return $this;
    }

    /**
     * Sets drop operation
     *
     * @param array $entityName
     *
     * @return $this
     */
    public function drop(array $entityName = [])
    {
        $this->buildDrop($this->retrieveModels($entityName));

        return $this;
    }

    /**
     * Returns array with models for operation
     *
     * @param array $entity
     *
     * @return ModelInterface[]
     */
    protected function retrieveModels(array $entity = [])
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
     * @param ModelInterface[] $models
     *
     * @throws SchemaException
     */
    protected function buildCreate(array $models)
    {
        $schemaManager = $this->connection->getSchemaManager();

        foreach ($models as $model) {
            if ($schemaManager->tablesExist([$model->table()])) {
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
     * @param ModelInterface[] $models
     */
    protected function buildAlter(array $models)
    {
        $fromSchema = $this->connection->getSchemaManager()->createSchema();
        $toSchema = clone $fromSchema;

        foreach ($models as $model) {
            if ($toSchema->hasTable($model->table())) {
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
     * @param ModelInterface[] $models
     */
    protected function buildDrop(array $models)
    {
        $fromSchema = $this->connection->getSchemaManager()->createSchema();
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
        foreach ($this->queryString() as $query) {
            $stmt = $this->connection->prepare($query);
            $stmt->execute();

            $result[] = $query;
        }

        $this->reset();

        return $result;
    }

    /**
     * Returns array of queries that will be executed
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
        $this->queries = [];
        $this->createCurrentSchema();

        return $this;
    }
}
