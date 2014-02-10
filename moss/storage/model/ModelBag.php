<?php
namespace moss\storage\model;

/**
 * Model bag
 *
 * @package  moss storage
 * @author   Michal Wachowski <wachowski.michal@gmail.com>
 */
class ModelBag
{
    /** @var array|ModelInterface */
    protected $collection = array();

    /** @var array|ModelInterface */
    protected $byAlias = array();

    /** @var array|ModelInterface */
    protected $byEntity = array();

    /**
     * Construct
     *
     * @param array $collection
     */
    public function __construct($collection = array())
    {
        $this->all($collection);
    }

    /**
     * Returns entity class name
     *
     * @param string|object $entity
     *
     * @return string
     */
    public function getEntityClass($entity)
    {
        if (is_object($entity)) {
            $entity = get_class($entity);
        }

        return ltrim($entity, '\\');
    }

    /**
     * Retrieves offset value
     *
     * @param string $alias
     *
     * @return ModelInterface
     * @throws ModelException
     */
    public function get($alias)
    {
        $alias = $this->getEntityClass($alias);

        if (isset($this->byAlias[$alias])) {
            return $this->byAlias[$alias];
        }

        if (isset($this->byEntity[$alias])) {
            return $this->byEntity[$alias];
        }

        throw new ModelException(sprintf('Model for entity "%s" does not exists', $alias));
    }

    /**
     * Sets value to offset
     *
     * @param ModelInterface $model
     * @param string         $alias
     *
     * @return $this
     */
    public function set(ModelInterface $model, $alias = null)
    {
        $hash = spl_object_hash($model);

        $this->collection[$hash] = & $model;

        $alias = $alias ? $alias : preg_replace('/_?[^\w\d]+/i', '_', $model->table());
        $this->byAlias[$alias] = &$this->collection[$hash];

        $entity = $model->entity() ? ltrim($model->entity(), '\\') : 'ClasslessEntity'.count($this->collection);
        $this->byEntity[$entity] = & $this->collection[$hash];

        return $this;
    }

    /**
     * Returns true if offset exists in bag
     *
     * @param string $alias
     *
     * @return bool
     */
    public function has($alias)
    {
        $alias = $this->getEntityClass($alias);

        if (isset($this->byAlias[$alias]) || isset($this->byEntity[$alias])) {
            return true;
        }

        return false;
    }

    /**
     * Returns all options
     * If array passed, becomes bag content
     *
     * @param array $array overwrites values
     *
     * @return array|ModelInterface[]
     */
    public function all($array = array())
    {
        if ($array !== array()) {
            foreach ($array as $key => $model) {
                $this->set($model, is_numeric($key) ? null : $key);
            }
        }

        return $this->collection;
    }

    /**
     * Count elements of an object
     *
     * @return int
     */
    public function count()
    {
        return count($this->collection);
    }

    /**
     * Return the current element
     *
     * @return mixed
     */
    public function current()
    {
        return current($this->collection);
    }

    /**
     * Return the key of the current element
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->collection);
    }

    /**
     * Move forward to next element
     *
     * @return void
     */
    public function next()
    {
        next($this->collection);
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @return void
     */
    public function rewind()
    {
        reset($this->collection);
    }

    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    public function valid()
    {
        $key = key($this->collection);

        if ($key === false || $key === null) {
            return false;
        }

        return isset($this->collection[$key]);
    }
}