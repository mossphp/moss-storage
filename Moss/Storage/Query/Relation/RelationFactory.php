<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query\Relation;

use Moss\Storage\Model\Definition\RelationInterface as RelationDefinitionInterface;
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Query\Query;
use Moss\Storage\Query\QueryException;

/**
 * Entity relationship factory
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class RelationFactory implements RelationFactoryInterface
{
    const RELATION_ONE = 'one';
    const RELATION_MANY = 'many';
    const RELATION_ONE_TROUGH = 'oneTrough';
    const RELATION_MANY_TROUGH = 'manyTrough';

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var ModelBag
     */
    protected $bag;

    protected $model;
    protected $relation;
    protected $conditions = [];
    protected $orders = [];
    protected $limit;
    protected $offset;

    /**
     * Constructor
     *
     * @param Query    $query
     * @param ModelBag $models
     */
    public function __construct(Query $query, ModelBag $models)
    {
        $this->query = $query;
        $this->bag = $models;
    }

    /**
     * @param ModelInterface              $model
     * @param RelationDefinitionInterface $relation
     *
     * @return $this
     */
    public function relation(ModelInterface $model, $relation)
    {
        $this->model = $model;
        $this->relation = $relation;

        return $this;
    }


    /**
     * Adds where condition to relation
     *
     * @param mixed  $field
     * @param mixed  $value
     * @param string $comparison
     * @param string $logical
     *
     * @return $this
     */
    public function where($field, $value, $comparison = '=', $logical = 'and')
    {
        $this->conditions[] = func_get_args();

        return $this;
    }

    /**
     * Adds sorting to relation
     *
     * @param string       $field
     * @param string|array $order
     *
     * @return $this
     */
    public function order($field, $order = 'desc')
    {
        $this->orders[] = func_get_args();

        return $this;
    }

    /**
     * Sets limits to relation
     *
     * @param int      $limit
     * @param null|int $offset
     *
     * @return $this
     */
    public function limit($limit, $offset = null)
    {
        $this->limit = $limit;
        $this->offset = $offset;
    }

    /**
     * Builds relation instance
     *
     * @return RelationInterface
     * @throws QueryException
     */
    public function build()
    {
        list($current, $further) = $this->splitRelationName($this->relation);
        $definition = $this->fetchDefinition($this->model, $current);

        switch ($definition->type()) {
            case self::RELATION_ONE:
                $instance = new OneRelation($this->query, $definition, $this->bag, $this);
                break;
            case self::RELATION_MANY:
                $instance = new ManyRelation($this->query, $definition, $this->bag, $this);
                break;
            case self::RELATION_ONE_TROUGH:
                $instance = new OneTroughRelation($this->query, $definition, $this->bag, $this);
                break;
            case self::RELATION_MANY_TROUGH:
                $instance = new ManyTroughRelation($this->query, $definition, $this->bag, $this);
                break;
            default:
                throw new QueryException(sprintf('Invalid read relation type "%s" for "%s"', $definition->type(), $definition->entity()));
        }

        $instance = $this->assignConditions($instance);
        $instance = $this->assignOrder($instance);
        $instance = $this->assignLimit($instance);

        if ($further) {
            $instance->with($further);
        }

        return $instance;
    }


    /**
     * Assigns conditions to relation
     *
     * @param RelationInterface $instance
     *
     * @return RelationInterface
     */
    protected function assignConditions(RelationInterface $instance)
    {
        foreach ($this->conditions as $node) {
            $instance->where($node[0], $node[1], isset($node[2]) ? $node[2] : '=', isset($node[3]) ? $node[3] : 'and');
        }

        return $instance;
    }

    /**
     * Assigns sorting to relation
     *
     * @param RelationInterface $instance
     *
     * @return RelationInterface
     */
    protected function assignOrder(RelationInterface $instance)
    {
        foreach ($this->orders as $node) {
            $instance->order($node[0], isset($node[1]) ? $node[1] : 'desc');
        }

        return $instance;
    }

    /**
     * Assigns limit to relation
     *
     * @param RelationInterface $instance
     *
     * @return RelationInterface
     */
    protected function assignLimit(RelationInterface $instance)
    {
        if ($this->limit !== null || $this->offset !== null) {
            $instance->limit($this->limit, $this->offset);
        }

        return $instance;
    }

    /**
     * Splits relation name
     *
     * @param string $relationName
     *
     * @return array
     */
    public function splitRelationName($relationName)
    {
        if (strpos($relationName, '.') !== false) {
            return explode('.', $relationName, 2);
        }

        return [$relationName, null];
    }

    /**
     * Fetches relation
     *
     * @param ModelInterface $model
     * @param string         $relation
     *
     * @return RelationDefinitionInterface
     * @throws QueryException
     */
    protected function fetchDefinition(ModelInterface $model, $relation)
    {
        if ($model->hasRelation($relation)) {
            return $model->relation($relation);
        }

        throw new QueryException(sprintf('Unable to resolve relation "%s" not found in model "%s"', $relation, $model->entity()));
    }

    /**
     * Resets builder
     *
     * @return $this
     */
    public function reset()
    {
        $this->model = null;
        $this->operation = null;
        $this->relation = null;
        $this->conditions = [];
        $this->orders = [];
        $this->limit = null;
        $this->offset = null;

        return $this;
    }
}
