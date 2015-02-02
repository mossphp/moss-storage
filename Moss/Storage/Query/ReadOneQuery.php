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


use Doctrine\DBAL\Connection;
use Moss\Storage\Converter\ConverterInterface;
use Moss\Storage\Model\Definition\FieldInterface;
use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Query\Relation\RelationFactoryInterface;


/**
 * Query used to read one entity from table
 *
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage
 */
class ReadOneQuery extends ReadQuery
{
    /**
     * Constructor
     *
     * @param Connection               $connection
     * @param ModelInterface           $model
     * @param ConverterInterface       $converter
     * @param RelationFactoryInterface $factory
     */
    public function __construct(Connection $connection, ModelInterface $model, ConverterInterface $converter, RelationFactoryInterface $factory)
    {
        parent::__construct($connection, $model, $converter, $factory);
        $this->limit(1);
    }

    /**
     * Executes query
     * After execution query is reset
     *
     * @return mixed
     * @throws QueryException
     */
    public function execute()
    {
        $stmt = $this->connection->prepare($this->queryString());
        $stmt->execute($this->binds);

        $result = $stmt->fetchAll(\PDO::FETCH_CLASS, $this->model->entity());

        if (!count($result)) {
            throw new QueryException(sprintf('Result out of range or does not exists for "%s"', $this->model->entity()));
        }

        $result = array_slice($result, 0, 1, false);

        $ref = new \ReflectionClass($this->model->entity());
        $this->restoreObject($result[0], $this->casts, $ref);

        foreach ($this->relations as $relation) {
            $result = $relation->read($result);
        }
        return $result[0];
    }

    /**
     * Resets adapter
     *
     * @return $this
     */
    public function reset()
    {
        parent::reset();
        $this->limit(1);

        return $this;
    }
}
