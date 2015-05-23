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

use Moss\Storage\GetTypeTrait;
use Moss\Storage\Model\Definition\FieldInterface;

/**
 * Query used to read data from table
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class WriteQuery extends AbstractEntityValueQuery implements WriteQueryInterface
{
    const EVENT_BEFORE = 'write.before';
    const EVENT_AFTER = 'write.after';

    use GetTypeTrait;

    protected $instance;

    protected $values = [];

    /**
     * Sets field names which values will be written
     *
     * @param array $fields
     *
     * @return $this
     */
    public function values($fields = [])
    {
        $this->values = [];

        if (empty($fields)) {
            foreach ($this->model->fields() as $field) {
                $this->assignValue($field);
            }

            return $this;
        }

        foreach ($fields as $field) {
            $this->assignValue($this->model->field($field));
        }

        return $this;
    }

    /**
     * Adds field which value will be written
     *
     * @param string $field
     *
     * @return $this
     */
    public function value($field)
    {
        $this->assignValue($this->model->field($field));

        return $this;
    }

    /**
     * Assigns value to query
     *
     * @param FieldInterface $field
     */
    protected function assignValue(FieldInterface $field)
    {
        $this->values[] = $field->name();
    }


    /**
     * Executes query
     * After execution query is reset
     *
     * @return mixed
     */
    public function execute()
    {
        $this->dispatcher->fire(self::EVENT_BEFORE, $this->instance);

        $this->buildQuery()
            ->execute();

        $this->dispatcher->fire(self::EVENT_AFTER, $this->instance);

        foreach ($this->relations as $relation) {
            $relation->write($this->instance);
        }

        return $this->instance;
    }

    /**
     * Returns current query string
     *
     * @return string
     */
    public function getSQL()
    {
        return $this->buildQuery()->getSQL();
    }

    /**
     * @return InsertQuery|UpdateQuery
     */
    protected function buildQuery()
    {
        if ($this->checkIfEntityExists()) {
            $query = new UpdateQuery($this->connection, $this->instance, $this->model, $this->factory, $this->accessor, $this->dispatcher);
        } else {
            $query = new InsertQuery($this->connection, $this->instance, $this->model, $this->factory, $this->accessor, $this->dispatcher);
        }

        $query->values($this->values);

        return $query;
    }

    /**
     * Returns true if entity exists database
     *
     * @return int
     */
    protected function checkIfEntityExists()
    {
        $query = new ReadQuery($this->connection, $this->model, $this->factory, $this->accessor, $this->dispatcher);

        foreach ($this->model->primaryFields() as $field) {
            $value = $this->accessor->getPropertyValue($this->instance, $field->name());

            if ($value === null) {
                return false;
            }

            $query->where($field->name(), $value, '=', 'and');
        }

        return $query->count() > 0;
    }

    /**
     * Returns array with bound values and their placeholders as keys
     *
     * @return array
     */
    public function binds()
    {
        return [];
    }

    /**
     * Resets adapter
     *
     * @return $this
     */
    public function reset()
    {
        $this->values = [];
        $this->relations = [];

        return $this;
    }
}
