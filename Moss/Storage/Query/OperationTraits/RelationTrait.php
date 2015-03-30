<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query\OperationTraits;


use Moss\Storage\Query\QueryException;
use Moss\Storage\Query\Relation\RelationFactoryInterface;
use Moss\Storage\Query\Relation\RelationInterface;

/**
 * Trait RelationTrait
 * Adds relational functionality
 *
 * @package Moss\Storage\Query\OperationTraits
 */
trait RelationTrait
{
    use AwareTrait;

    /**
     * @var RelationFactoryInterface
     */
    private $factory;

    /**
     * @var RelationInterface[]
     */
    protected $relations = [];

    /**
     * Adds relation to query
     *
     * @param string|array $relation
     *
     * @return $this
     * @throws QueryException
     */
    public function with($relation)
    {
        foreach ((array) $relation as $node) {
            $this->factory->reset();
            $instance = $this->factory->relation($this->model(), $node)->build();
            $this->setRelation($instance);
        }

        return $this;
    }

    /**
     * Returns relation instance
     *
     * @param string $relation
     *
     * @return RelationInterface
     * @throws QueryException
     */
    public function relation($relation)
    {
        list($relation, $furtherRelations) = $this->factory->splitRelationName($relation);

        $instance = $this->getRelation($relation);

        if ($furtherRelations) {
            return $instance->relation($furtherRelations);
        }

        return $instance;
    }

    /**
     * Adds relation to query or if relation with same name exists - replaces it with new one
     *
     * @param RelationInterface $relation
     *
     * @return $this
     */
    public function setRelation(RelationInterface $relation)
    {
        $this->relations[$relation->name()] = $relation;

        return $this;
    }

    /**
     * Returns relation with set name
     *
     * @param string $name
     *
     * @return RelationInterface
     * @throws QueryException
     */
    public function getRelation($name)
    {
        if (!isset($this->relations[$name])) {
            throw new QueryException(sprintf('Unable to retrieve relation "%s" query, relation does not exists in query "%s"', $name, $this->model()->entity()));
        }

        return $this->relations[$name];
    }
}
