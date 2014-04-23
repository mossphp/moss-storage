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
     * @param string $entity
     * @param string $type
     * @param string $join
     *
     * @return JoinInterface
     * @throws QueryException
     */
    public function create($entity, $type, $join)
    {
        $model = $this->bag->get($entity);

        if (!$model->hasRelation($join)) {
            throw new QueryException(sprintf('Unable to join "%s" in query "%s" undefined relation', $join, $model->entity()));
        }

        $relation = $model->relation($join);

        return new Join(
            $type,
            $relation,
            $this->bag->get($entity),
            $this->bag->get($join),
            in_array($relation->type(), array('oneTrough', 'manyTrough')) ? $this->bag->get($relation->mediator()) : null,
            $join
        );
    }
} 