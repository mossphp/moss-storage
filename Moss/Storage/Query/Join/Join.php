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

    /** @var RelationInterface */
    protected $relation;

    /** @var ModelInterface */
    protected $source;

    /** @var ModelInterface */
    protected $target;

    /** @var ModelInterface */
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

        $this->buildConditions($this->relation->localValues(), $this->source->table(), $this->conditions);
        $this->buildConditions($this->relation->foreignValues(), $this->target->table(), $this->conditions);

        if (in_array($relation->type(), array('one', 'many'))) {
            $this->joints[] = $this->buildJoint(
                $this->type,
                $this->target->table(),
                $this->relation->keys(),
                $this->source->table(),
                $this->target->table()
            );

            return;
        }

        if (in_array($relation->type(), array('oneTrough', 'manyTrough'))) {
            $this->joints[] = $this->buildJoint(
                $this->type,
                $this->mediator->table(),
                $this->relation->localKeys(),
                $this->source->table(),
                $this->mediator->table()
            );

            $this->joints[] = $this->buildJoint(
                $this->type,
                $this->target->table(),
                $this->relation->foreignKeys(),
                $this->mediator->table(),
                $this->target->table()
            );

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

    /**
     * Builds joint definition
     *
     * @param string $type
     * @param string $table
     * @param array  $keys
     * @param string $source
     * @param string $target
     *
     * @return array
     */
    protected function buildJoint($type, $table, $keys, $source, $target)
    {
        return array($type, $table, $this->prefixKeys($keys, $source, $target));
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

    /**
     * @param array  $keys
     * @param string $table
     * @param array  $conditions
     */
    protected function buildConditions($keys, $table, &$conditions = array())
    {
        foreach ($keys as $field => $value) {
            $conditions[] = array($this->buildField($field, $table), $value, '=', 'and');
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
