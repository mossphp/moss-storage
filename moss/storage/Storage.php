<?php
namespace moss\storage;

use moss\storage\builder\BuilderInterface;
use moss\storage\builder\QueryBuilderInterface;
use moss\storage\builder\SchemaBuilderInterface;
use moss\storage\driver\DriverInterface;
use moss\storage\model\ModelInterface;
use moss\storage\query\EntityQueryInterface;
use moss\storage\query\Query;
use moss\storage\query\relation\RelationInterface;
use moss\storage\query\relation\One;
use moss\storage\query\relation\Many;
use moss\storage\query\Schema;
use moss\storage\query\SchemaQueryInterface;

/**
 * Storage
 *
 * @package moss Storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 */
class Storage implements StorageInterface
{

    /** @var DriverInterface */
    protected $driver;

    /** @var BuilderInterface */
    protected $builders = array(
        'query' => null,
        'schema' => null
    );

    /** @var ModelInterface[] */
    protected $models = array();

    /** @var ModelInterface[] */
    protected $alias = array();

    /**
     * Constructor
     * If Modeler passed - modeler will be used to build models on the run when not present in storage
     *
     * @param DriverInterface    $driver
     * @param BuilderInterface[] $builders
     *
     * @throws StorageException
     */
    function __construct(DriverInterface $driver, array $builders)
    {
        $this->driver = & $driver;

        foreach ($builders as $builder) {
            if (!$builder instanceof BuilderInterface) {
                throw new StorageException('Builder must be an instance of BuilderInterface');
            }

            if ($builder instanceof QueryBuilderInterface) {
                $this->builders['query'] = & $builder;
            }

            if ($builder instanceof SchemaBuilderInterface) {
                $this->builders['schema'] = & $builder;
            }

            unset($builder);
        }
    }

    /**
     * Returns adapter instance for specified entity
     *
     * @return DriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Returns adapter instance for specified entity
     *
     * @return QueryBuilderInterface
     */
    public function getQuery()
    {
        return $this->builders['query'];
    }

    /**
     * Returns adapter instance for specified entity
     *
     * @return SchemaBuilderInterface
     */
    public function getSchema()
    {
        return $this->builders['schema'];
    }

    /**
     * Registers model into storage
     *
     * @param string         $alias
     * @param ModelInterface $model
     *
     * @return Storage
     */
    public function registerModel($alias, ModelInterface $model)
    {
        $this->models[$model->entity()] = $model;
        $this->alias[$alias] = & $this->models[$model->entity()];

        return $this;
    }

    /**
     * Returns entity class name
     *
     * @param string|object $entity
     *
     * @return string
     */
    protected function getEntityClass($entity)
    {
        if (is_object($entity)) {
            $entity = get_class($entity);
        }

        return ltrim($entity, '\\');
    }

    /**
     * Returns true if model exists
     *
     * @param string|object $entityClass
     *
     * @return bool
     */
    public function hasModel($entityClass)
    {
        return isset($this->models[$this->getEntityClass($entityClass)]);
    }

    /**
     * Returns model instance
     *
     * @param string|object $entityClass
     *
     * @return model\ModelInterface
     * @throws StorageException
     */
    public function getModel($entityClass)
    {
        $entityClass = $this->getEntityClass($entityClass);

        if (isset($this->models[$entityClass])) {
            return $this->models[$entityClass];
        }

        if (isset($this->alias[$entityClass])) {
            return $this->alias[$entityClass];
        }

        throw new StorageException(sprintf('Model for entity "%s" does not exists', $entityClass));
    }

    /**
     * Returns all registered models
     *
     * @return array|ModelInterface
     */
    public function getModels()
    {
        return $this->models;
    }

    /**
     * Returns true if requested entity class has defined relation
     *
     * @param string $entityClass
     * @param string $relationName
     *
     * @return bool
     */
    public function hasRelation($entityClass, $relationName)
    {
        list($relationName,) = $this->splitRelationName($relationName);

        if (!$this->hasModel($entityClass)) {
            return false;
        }

        return $this
            ->getModel($entityClass)
            ->hasRelation($relationName);
    }

    /**
     * Returns relation instance
     *
     * @param string $entityClass
     * @param string $relationName
     * @param bool   $transparent
     *
     * @return mixed
     * @throws StorageException
     */
    public function getRelation($entityClass, $relationName, $transparent = null)
    {
        list($relationName, $furtherRelations) = $this->splitRelationName($relationName);

        $relation = $this
            ->getModel($entityClass)
            ->relation($relationName);

        $model = $this->getModel($relation->entity());

        $query = new Query($this->driver, $this->getQuery(), $model, 'read');

        switch ($relation->type()) {
            case RelationInterface::RELATION_ONE:
                $relation = new One($query, $relation);
                break;
            case RelationInterface::RELATION_MANY:
                $relation = new Many($query, $relation);
                break;
            default:
                throw new StorageException(sprintf('Invalid relation type "%s" in relation "%s" for "%s"', $relation->type(), $relationName, $entityClass));
        }

        $relation->transparent($transparent);

        if ($furtherRelations) {
            $relation
                ->query()
                ->setRelation($this->getRelation($model->entity(), $furtherRelations, $transparent));
        }

        return $relation;
    }

    /**
     * Splits relation name into relation and further splitable name
     *
     * @param string $relationName
     *
     * @return array
     */
    protected function splitRelationName($relationName)
    {
        $furtherRelations = null;
        if (strpos($relationName, '.') !== false) {
            return explode('.', $relationName, 2);
        }

        return array($relationName, null);
    }

    /**
     * Returns true if entity container exists
     *
     * @param string|object $entityClass
     *
     * @return Schema
     */
    public function check($entityClass)
    {
        return new Schema(
            $this->driver,
            $this->getSchema(),
            $this->getModel($this->getEntityClass($entityClass)),
            SchemaQueryInterface::OPERATION_CHECK
        );
    }

    /**
     * Returns model build from container data
     *
     * @param string|object $entityClass
     *
     * @return Schema
     */
    public function info($entityClass)
    {
        return new Schema(
            $this->driver,
            $this->getSchema(),
            $this->getModel($this->getEntityClass($entityClass)),
            SchemaQueryInterface::OPERATION_INFO
        );
    }

    /**
     * Returns query creating entity container
     *
     * @param string|object $entityClass
     *
     * @return Schema
     */
    public function create($entityClass)
    {
        return new Schema(
            $this->driver,
            $this->getSchema(),
            $this->getModel($this->getEntityClass($entityClass)),
            SchemaQueryInterface::OPERATION_CREATE
        );
    }

    /**
     * Returns query altering entity container
     *
     * @param string|object $entityClass
     *
     * @return Schema
     */
    public function alter($entityClass)
    {
        return new Schema(
            $this->driver,
            $this->getSchema(),
            $this->getModel($this->getEntityClass($entityClass)),
            SchemaQueryInterface::OPERATION_ALTER
        );
    }

    /**
     * Returns query removing entity container
     *
     * @param string|object $entityClass
     *
     * @return Schema
     */
    public function drop($entityClass)
    {
        return new Schema(
            $this->driver,
            $this->getSchema(),
            $this->getModel($this->getEntityClass($entityClass)),
            SchemaQueryInterface::OPERATION_DROP
        );
    }

    /**
     * Returns count query for passed entity class
     *
     * @param string $entityClass
     *
     * @return Query
     * @throws StorageException
     */
    public function count($entityClass)
    {
        return new Query(
            $this->driver,
            $this->getQuery(),
            $this->getModel($this->getEntityClass($entityClass)),
            EntityQueryInterface::OPERATION_COUNT
        );
    }

    /**
     * Returns read single entity for passed entity class
     *
     * @param string $entityClass
     *
     * @return Query
     * @throws StorageException
     */
    public function readOne($entityClass)
    {
        return new Query(
            $this->driver,
            $this->getQuery(),
            $this->getModel($this->getEntityClass($entityClass)),
            EntityQueryInterface::OPERATION_READ_ONE
        );
    }

    /**
     * Returns read query for passed entity class
     *
     * @param string $entityClass
     *
     * @return Query
     * @throws StorageException
     */
    public function read($entityClass)
    {
        return new Query(
            $this->driver,
            $this->getQuery(),
            $this->getModel($this->getEntityClass($entityClass)),
            EntityQueryInterface::OPERATION_READ
        );
    }

    /**
     * Returns insert query for passed entity object or entity class
     *
     * @param string|object $entity
     *
     * @return Query
     * @throws StorageException
     */
    public function insert($entity)
    {
        return new Query(
            $this->driver,
            $this->getQuery(),
            $this->getModel($this->getEntityClass($entity)),
            EntityQueryInterface::OPERATION_INSERT,
            $entity
        );
    }

    /**
     * Returns write query for passed entity object or entity class
     *
     * @param string|object $entity
     *
     * @return Query
     * @throws StorageException
     */
    public function write($entity)
    {
        return new Query(
            $this->driver,
            $this->getQuery(),
            $this->getModel($this->getEntityClass($entity)),
            EntityQueryInterface::OPERATION_WRITE,
            $entity
        );
    }

    /**
     * Returns update query for passed entity object or entity class
     *
     * @param string|object $entity
     *
     * @return Query
     * @throws StorageException
     */
    public function update($entity)
    {
        return new Query(
            $this->driver,
            $this->getQuery(),
            $this->getModel($this->getEntityClass($entity)),
            EntityQueryInterface::OPERATION_UPDATE,
            $entity
        );
    }

    /**
     * Returns delete query for passed entity object or entity class
     *
     * @param string|object $entity
     *
     * @return Query
     * @throws StorageException
     */
    public function delete($entity)
    {
        return new Query(
            $this->driver,
            $this->getQuery(),
            $this->getModel($this->getEntityClass($entity)),
            EntityQueryInterface::OPERATION_DELETE,
            $entity
        );
    }

    /**
     * Returns clear query for passed entity class
     *
     * @param string $entityClass
     *
     * @return Query
     * @throws StorageException
     */
    public function clear($entityClass)
    {
        return new Query(
            $this->driver,
            $this->getQuery(),
            $this->getModel($this->getEntityClass($entityClass)),
            EntityQueryInterface::OPERATION_CLEAR
        );
    }

    /**
     * Starts transaction
     *
     * @return $this
     */
    public function transactionStart()
    {
        $this->driver->transactionStart();

        return $this;
    }

    /**
     * Commits transaction
     *
     * @return $this
     */
    public function transactionCommit()
    {
        $this->driver->transactionCommit();

        return $this;
    }

    /**
     * RollBacks transaction
     *
     * @return $this
     */
    public function transactionRollback()
    {
        $this->driver->transactionRollback();

        return $this;
    }

    /**
     * Returns true if in transaction
     *
     * @return bool
     */
    public function transactionCheck()
    {
        return $this->driver->transactionCheck();
    }
}
