<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query\Join;

use Moss\Storage\Model\Definition\RelationInterface as RelationDefinitionInterface;
use Moss\Storage\Model\ModelBag;
use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Query\QueryException;

/**
 * Table join factory
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class JoinFactory
{
    /**
     * @var ModelBag
     */
    private $bag;

    /**
     * Constructor
     *
     * @param ModelBag $bag
     */
    public function __construct(ModelBag $bag)
    {
        $this->bag = & $bag;
    }

    /**
     * Creates join instance
     *
     * @param string $entity
     * @param string $type
     * @param string $join
     *
     * @return JoinInterface
     * @throws QueryException
     */
    public function create($entity, $type, $join)
    {
        $model = $this->bag->get($entity);
        $relation = $this->fetchDefinition($model, $join);

        return new Join(
            $type,
            $relation,
            $this->bag->get($entity),
            $this->bag->get($join),
            in_array($relation->type(), array('oneTrough', 'manyTrough')) ? $this->bag->get($relation->mediator()) : null
        );
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
    private function fetchDefinition(ModelInterface $model, $relation)
    {
        if ($model->hasRelation($relation)) {
            return $model->relation($relation);
        }

        if ($this->bag->has($relation)) {
            $entity = $this->bag->get($relation);

            if ($model->hasRelation($entity->alias())) {
                return $model->relation($entity->alias());
            }

            if ($model->hasRelation($entity->entity())) {
                return $model->relation($entity->entity());
            }
        }

        throw new QueryException(sprintf('Unable to resolve relation "%s" for join in model "%s"', $relation, $model->entity()));
    }
}
