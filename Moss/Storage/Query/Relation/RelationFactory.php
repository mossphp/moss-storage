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
use Moss\Storage\Query\QueryException;
use Moss\Storage\Query\StorageInterface;

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
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var ModelBag
     */
    protected $bag;

    /**
     * Constructor
     *
     * @param StorageInterface $storage
     * @param ModelBag         $models
     */
    public function __construct(StorageInterface $storage, ModelBag $models)
    {
        $this->storage = $storage;
        $this->bag = $models;
    }

    /**
     * Sets model and its relation
     *
     * @param ModelInterface $model
     * @param string         $relation
     *
     * @return RelationInterface
     * @throws RelationException
     */
    public function build(ModelInterface $model, $relation)
    {
        list($current, $further) = $this->splitRelationName($relation);
        $definition = $this->fetchDefinition($model, $current);

        switch ($definition->type()) {
            case self::RELATION_ONE:
                $instance = new OneRelation($this->storage, $definition, $this->bag, $this);
                break;
            case self::RELATION_MANY:
                $instance = new ManyRelation($this->storage, $definition, $this->bag, $this);
                break;
            case self::RELATION_ONE_TROUGH:
                $instance = new OneTroughRelation($this->storage, $definition, $this->bag, $this);
                break;
            case self::RELATION_MANY_TROUGH:
                $instance = new ManyTroughRelation($this->storage, $definition, $this->bag, $this);
                break;
            default:
                throw new RelationException(sprintf('Invalid relation type "%s" for "%s"', $definition->type(), $definition->entity()));
        }

        if ($further) {
            $instance->with($further);
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

        throw new RelationException(sprintf('Unable to resolve relation "%s" not found in model "%s"', $relation, $model->entity()));
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
