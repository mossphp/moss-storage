<?php
namespace moss\storage;

use moss\storage\query\QueryInterface;
use moss\storage\model\ModelInterface;
use moss\storage\driver\DriverInterface;
use moss\storage\adapter\AdapterInterface;
use moss\storage\relation\RelationInterface;

/**
 * Storage interface
 *
 * @package moss Storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 */
interface StorageInterface
{
    /**
     * Returns adapter instance
     *
     * @return DriverInterface
     */
    public function getDriver();

    /**
     * Returns adapter instance
     *
     * @return AdapterInterface
     */
    public function getSchema();

    /**
     * Registers model into storage
     *
     * @param string         $alias
     * @param ModelInterface $model
     *
     * @return StorageInterface
     */
    public function registerModel($alias, ModelInterface $model);

    /**
     * Returns true if model exists
     *
     * @param string|object $entityClass
     *
     * @return bool
     */
    public function hasModel($entityClass);

    /**
     * Returns model instance
     *
     * @param string|object $entityClass
     *
     * @return ModelInterface
     */
    public function getModel($entityClass);

    /**
     * Returns all registered models
     *
     * @return array|ModelInterface
     */
    public function getModels();

    /**
     * Returns relation instance
     *
     * @param string $entityClass
     * @param string $relationName
     * @param bool   $transparent
     *
     * @return RelationInterface
     */
    public function getRelation($entityClass, $relationName, $transparent = null);

    /**
     * Returns true if entity container exists
     *
     * @param string|object $entityClass
     *
     * @return QueryInterface
     */
    public function check($entityClass);

    /**
     * Returns query creating entity container
     *
     * @param string|object $entityClass
     *
     * @return QueryInterface
     */
    public function create($entityClass);

    /**
     * Returns query altering entity container
     *
     * @param string|object $entityClass
     *
     * @return QueryInterface
     */
    public function alter($entityClass);

    /**
     * Returns query removing entity container
     *
     * @param string|object $entityClass
     *
     * @return QueryInterface
     */
    public function drop($entityClass);

    /**
     * Returns count query for passed entity class
     *
     * @param string $entityClass
     *
     * @return QueryInterface
     */
    public function count($entityClass);

    /**
     * Returns read single entity for passed entity class
     *
     * @param string $entityClass
     *
     * @return QueryInterface
     */
    public function readOne($entityClass);

    /**
     * Returns read query for passed entity class
     *
     * @param string $entityClass
     *
     * @return QueryInterface
     */
    public function read($entityClass);

    /**
     * Returns insert query for passed entity object or entity class
     *
     * @param string|object $entity
     *
     * @return QueryInterface
     */
    public function insert($entity);

    /**
     * Returns write query for passed entity object or entity class
     *
     * @param string|object $entity
     *
     * @return QueryInterface
     */
    public function write($entity);

    /**
     * Returns update query for passed entity object or entity class
     *
     * @param string|object $entity
     *
     * @return QueryInterface
     */
    public function update($entity);

    /**
     * Returns delete query for passed entity object or entity class
     *
     * @param string|object $entity
     *
     * @return QueryInterface
     */
    public function delete($entity);

    /**
     * Clears entity container
     *
     * @param string $entityClass
     *
     * @return QueryInterface
     */
    public function clear($entityClass);

    /**
     * Starts transaction
     *
     * @return $this
     */
    public function transactionStart();

    /**
     * Commits transaction
     *
     * @return $this
     */
    public function transactionCommit();

    /**
     * RollBacks transaction
     *
     * @return $this
     */
    public function transactionRollback();

    /**
     * Returns true if in transaction
     *
     * @return bool
     */
    public function transactionCheck();
}
