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


use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Model\Definition\FieldInterface;
use Moss\Storage\Model\Definition\RelationInterface;
use Moss\Storage\Query\QueryException;

/**
 * Table join definition
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class Join implements JoinInterface
{

    protected $type;
    protected $relation;
    protected $source;
    protected $mediator;

    private $joints = array();
    private $conditions = array();

    /**
     * Constructor
     *
     * @param string            $type
     * @param RelationInterface $relation
     * @param ModelInterface    $source
     * @param ModelInterface    $target
     * @param ModelInterface    $mediator
     *
     * @throws QueryException
     */
    public function __construct($type, RelationInterface $relation, ModelInterface $source, ModelInterface $target, ModelInterface $mediator = null)
    {
        $this->type = $type;
        $this->relation = $relation;
        $this->source = $source;
        $this->target = $target;
        $this->mediator = $mediator;

        if (in_array($relation->type(), array('one', 'many'))) {
            $this->joinSimpleRelations($type, $source, $relation);

            return;
        }

        if (in_array($relation->type(), array('oneTrough', 'manyTrough'))) {
            $this->joinTroughRelations($type, $source, $relation);

            return;
        }

        throw new QueryException(sprintf('Unable to create join for "%s" invalid relation type', $this->entity()));
    }

    /**
     * Returns join type
     *
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Returns join alias
     *
     * @return string
     */
    public function alias()
    {
        return $this->target->alias();
    }

    /**
     * Returns true if joined entity is named
     *
     * @param string $name
     *
     * @return string
     */
    public function isNamed($name)
    {
        return $this->target->isNamed($name);
    }

    /**
     * Returns entity name
     *
     * @return string
     */
    public function entity()
    {
        return $this->target->entity();
    }

    /**
     * Returns field definitions from joined model
     *
     * @return FieldInterface[]
     */
    public function fields()
    {
        return $this->target->fields();
    }


    /**
     * Returns field definition from joined model
     *
     * @param string $field
     *
     * @return FieldInterface
     */
    public function field($field)
    {
        return $this->target->field($field);
    }

    /**
     * Returns joins
     *
     * @return array
     */
    public function joints()
    {
        return $this->joints;
    }

    /**
     * Returns join conditions
     *
     * @return array
     */
    public function conditions()
    {
        return $this->conditions;
    }

    protected function joinSimpleRelations()
    {
        $this->joints[] = array(
            $this->type,
            $this->target->table(),
            $this->prefixKeys($this->relation->keys(), $this->source->table(), $this->target->table())
        );

        $this->joinConditions();
    }

    protected function joinTroughRelations()
    {
        $this->joints[] = array(
            $this->type,
            $this->mediator->table(),
            $this->prefixKeys($this->relation->localKeys(), $this->source->table(), $this->mediator->table())
        );

        $this->joints[] = array(
            $this->type,
            $this->target->table(),
            $this->prefixKeys($this->relation->foreignKeys(), $this->mediator->table(), $this->target->table())
        );

        $this->joinConditions();
    }

    /**
     * Prefixes local/foreign keys with table name
     *
     * @param array $keys
     * @param       $localPrefix
     * @param       $foreignPrefix
     *
     * @return array
     */
    protected function prefixKeys(array $keys, $localPrefix, $foreignPrefix)
    {
        $result = array();
        foreach ($keys as $local => $foreign) {
            $result[$this->buildField($local, $localPrefix)] = $this->buildField($foreign, $foreignPrefix);
        }

        return $result;
    }

    protected function joinConditions()
    {
        foreach ($this->relation->localValues() as $field => $value) {
            $this->conditions[] = array($this->buildField($field, $this->source->table()), $value, '=', 'and');
        }

        foreach ($this->relation->foreignValues() as $field => $value) {
            $this->conditions[] = array($this->buildField($field, $this->target->table()), $value, '=', 'and');
        }
    }

    /**
     * Builds field name prefixed with optional table name
     *
     * @param string      $field
     * @param null|string $table
     *
     * @return string
     */
    protected function buildField($field, $table)
    {
        if ($table === null) {
            return $field;
        }

        return $table . '.' . $field;
    }
}
