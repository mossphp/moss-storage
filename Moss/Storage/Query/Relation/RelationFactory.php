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
use Moss\Storage\Query\QueryBaseInterface;
use Moss\Storage\Query\QueryException;
use Moss\Storage\Query\QueryInterface;

/**
 * Entity relationship factory
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class RelationFactory
{

    /**
     * @var QueryInterface
     */
    private $query;

    /**
     * @var ModelBag
     */
    private $bag;

    /**
     * Constructor
     *
     * @param QueryInterface $query
     * @param ModelBag       $models
     */
    public function __construct(QueryInterface $query, ModelBag $models)
    {
        $this->query = $query;
        $this->bag = $models;
    }

    /**
     * Adds relation to query with optional conditions and sorting (as key value pairs)
     *
     * @param ModelInterface $model
     * @param string|array   $relation
     * @param array          $conditions
     * @param array          $order
     *
     * @return $this
     */
    public function create(ModelInterface $model, $relation, array $conditions = [], array $order = [])
    {
        if (!is_array($relation)) {
            return $this->createSingleRelation($model, $relation, $conditions, $order);
        }

        return $this->createMultipleRelations($model, $relation, $conditions, $order);
    }

    protected function createSingleRelation(ModelInterface $model, $relation, array $conditions = [], array $order = [])
    {
        list($current, $further) = $this->splitRelationName($relation);
        $instance = $this->assignRelation($model, $current, $conditions, $order);

        if ($further) {
            $instance->with($further);
        }

        return [$instance];
    }

    protected function createMultipleRelations(ModelInterface $model, array $relations, array $conditions = [], array $order = [])
    {
        $instances = [];
        foreach ($relations as $i => $relation) {
            list($current, $further) = $this->splitRelationName($relation);
            if (!isset($instances[$current])) {
                $instances[$current] = $this->assignRelation(
                    $model,
                    $current,
                    isset($conditions[$i]) ? $conditions[$i] : [],
                    isset($order[$i]) ? $order[$i] : []
                );
            }

            if ($further) {
                $instances[$current]->with($further);
            }
        }

        return $instances;
    }

    /**
     * Assigns relation to query
     *
     * @param ModelInterface $model
     * @param string         $relation
     * @param array          $conditions
     * @param array          $order
     *
     * @return RelationInterface
     * @throws QueryException
     */
    protected function assignRelation($model, $relation, array $conditions = [], array $order = [])
    {
        $instance = $this->buildRelationInstance($this->fetchDefinition($model, $relation));

        foreach ($conditions as $node) {
            if (!is_array($node)) {
                throw new QueryException(sprintf('Invalid condition, must be an array, got %s', gettype($node)));
            }

            $instance->query()
                ->where($node[0], $node[1], isset($node[2]) ? $node[2] : '=', isset($node[3]) ? $node[3] : 'and');
        }

        foreach ($order as $node) {
            if (!is_array($node)) {
                throw new QueryException(sprintf('Invalid order, must be an array, got %s', gettype($node)));
            }

            $instance->query()
                ->order($node[0], isset($node[1]) ? $node[1] : 'desc');
        }

        return $instance;
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
     * Builds relation instance
     *
     * @param RelationDefinitionInterface $definition
     *
     * @return ManyRelation|ManyTroughRelation|OneRelation|OneTroughRelation
     * @throws QueryException
     */
    protected function buildRelationInstance(RelationDefinitionInterface $definition)
    {
        $query = clone $this->query;
        $query->read($definition->entity());

        switch ($definition->type()) {
            case 'one':
                return new OneRelation($query, $definition, $this->bag);
            case 'many':
                return new ManyRelation($query, $definition, $this->bag);
            case 'oneTrough':
                return new OneTroughRelation($query, $definition, $this->bag);
            case 'manyTrough':
                return new ManyTroughRelation($query, $definition, $this->bag);
            default:
                throw new QueryException(sprintf('Invalid relation type "%s" for "%s"', $definition->type(), $definition->entity()));
        }
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
}
