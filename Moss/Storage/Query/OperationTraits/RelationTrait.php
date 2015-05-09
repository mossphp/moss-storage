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


use Moss\Storage\Model\ModelInterface;
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
            $this->relations[$instance->name()] = $instance;
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

        if (!isset($this->relations[$relation])) {
            throw new QueryException(sprintf('Unable to retrieve relation "%s" query, relation does not exists in query "%s"', $relation, $this->model()->entity()));
        }

        if ($furtherRelations) {
            return $this->relations[$relation]->relation($furtherRelations);
        }

        return $this->relations[$relation];
    }

    /**
     * Returns model
     *
     * @return ModelInterface
     */
    abstract public function model();
}
