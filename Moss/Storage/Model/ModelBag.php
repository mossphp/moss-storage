<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Model;

use Moss\Storage\NormalizeNamespaceTrait;

/**
 * Registry containing models
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class ModelBag
{
    use NormalizeNamespaceTrait;

    /**
     * @var array|ModelInterface
     */
    protected $collection = [];

    /**
     * @var array|ModelInterface
     */
    protected $byAlias = [];

    /**
     * @var array|ModelInterface
     */
    protected $byEntity = [];

    /**
     * Construct
     *
     * @param array $collection
     */
    public function __construct($collection = [])
    {
        $this->all($collection);
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
        $alias = $this->normalizeNamespace($alias);

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

        $this->collection[$hash] = &$model;

        if ($alias !== null) {
            $this->byAlias[$model->alias($alias)] = &$this->collection[$hash];
        }

        $this->byEntity[$this->normalizeNamespace($model->entity())] = &$this->collection[$hash];

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
        $alias = $this->normalizeNamespace($alias);

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
    public function all($array = [])
    {
        if (!empty($array)) {
            foreach ($array as $key => $model) {
                $this->set($model, is_numeric($key) ? null : $key);
            }
        }

        return $this->byAlias;
    }
}
