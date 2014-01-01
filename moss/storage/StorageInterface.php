<?php
namespace moss\storage;

use moss\storage\model\ModelInterface;
use moss\storage\model\ModelBag;
use moss\storage\driver\DriverInterface;
use moss\storage\query\SchemaInterface;
use moss\storage\query\QueryInterface;

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
     * Registers model into storage
     *
     * @param string         $alias
     * @param ModelInterface $model
     *
     * @return StorageInterface
     */
    public function register($alias, ModelInterface $model);

    /**
     * Returns all registered models
     *
     * @return ModelBag
     */
    public function models();

    /**
     * Returns true if entity table exists
     *
     * @param string|object $entityClass
     *
     * @return SchemaInterface
     */
    public function check($entityClass = null);

    /**
     * Returns query creating entity table
     *
     * @param string|object $entityClass
     *
     * @return SchemaInterface
     */
    public function create($entityClass = null);

    /**
     * Returns query altering entity table
     *
     * @param string|object $entityClass
     *
     * @return SchemaInterface
     */
    public function alter($entityClass = null);

    /**
     * Returns query removing entity table
     *
     * @param string|object $entityClass
     *
     * @return SchemaInterface
     */
    public function drop($entityClass = null);

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
     * Clears entity table
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
