<?php

/*
* This file is part of the moss-storage package
*
* (c) Michal Wachowski <wachowski.michal@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Moss\Storage\Query;

use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Query\Relation\RelationFactoryInterface;
use Moss\Storage\Query\Relation\RelationInterface;

/**
 * Abstract Relational
 * Class implementing relational interface
 *
 * @package Moss\Storage
 */
abstract class AbstractRelational
{
    /**
     * @var RelationFactoryInterface
     */
    protected $factory;

    /**
     * @var RelationInterface[]
     */
    protected $relations = [];

    /**
     * Returns model
     *
     * @return ModelInterface
     */
    abstract public function model();

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
            $instance = $this->factory->build($this->model(), $node);
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
        list($name, $furtherRelations) = $this->factory->splitRelationName($relation);

        if (!isset($this->relations[$name])) {
            throw new QueryException(sprintf('Unable to retrieve relation "%s" query, relation does not exists in query "%s"', $name, $this->model()->entity()));
        }

        if ($furtherRelations) {
            return $this->relations[$name]->relation($furtherRelations);
        }

        return $this->relations[$name];
    }
}
