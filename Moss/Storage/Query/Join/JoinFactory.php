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


use Moss\Storage\Model\ModelBag;
use Moss\Storage\Model\ModelInterface;
use Moss\Storage\Query\QueryException;

class JoinFactory
{
    /** @var ModelBag */
    private $bag;

    public function __construct(ModelBag $bag)
    {
        $this->bag = & $bag;
    }

    /**
     * Creates join instance
     *
     * @param ModelInterface $model
     * @param string         $type
     * @param string         $entity
     *
     * @return JoinInterface
     * @throws QueryException
     */
    public function create(ModelInterface $model, $type, $entity)
    {
        if (!$model->hasRelation($entity)) {
            throw new QueryException(sprintf('Unable to join "%s" in query "%s" undefined relation', $entity, $model->entity()));
        }

        $relation = $model->relation($entity);

        return new Join(
            $type,
            $relation,
            $this->bag->get($entity),
            $relation->mediator() ? $this->bag->get($relation->mediator()) : null,
            $entity
        );
    }
} 