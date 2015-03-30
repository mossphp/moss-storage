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


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Moss\Storage\Model\ModelInterface;

/**
 * Trait AwareTrait
 * Abstract trait enforcing basic methods
 *
 * @package Moss\Storage\Query\OperationTraits
 */
trait AwareTrait
{
    /**
     * Returns connection
     *
     * @return Connection
     */
    abstract public function connection();

    /**
     * Returns model
     *
     * @return ModelInterface
     */
    abstract public function model();

    /**
     * Returns query builder instance
     *
     * @return QueryBuilder
     */
    abstract public function query();

    /**
     * Binds value to unique key and returns it
     *
     * @param string $operation
     * @param string $field
     * @param string $type
     * @param mixed  $value
     *
     * @return string
     */
    abstract protected function bind($operation, $field, $type, $value);

    /**
     * Returns var type
     *
     * @param mixed $var
     *
     * @return string
     */
    abstract protected function getType($var);
}
