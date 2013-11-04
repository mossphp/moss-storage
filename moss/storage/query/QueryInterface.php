<?php
namespace moss\storage\query;

use moss\storage\builder\BuilderInterface;
use moss\storage\driver\DriverInterface;
use moss\storage\model\ModelInterface;

/**
 * Query representation
 *
 * @package moss Storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 */
interface QueryInterface
{
    /**
     * Returns adapter instance used in query
     *
     * @return DriverInterface
     */
    public function getDriver();

    /**
     * Returns adapter instance used in query
     *
     * @return BuilderInterface
     */
    public function getBuilder();

    /**
     * Returns model instance used in query
     *
     * @return ModelInterface
     */
    public function getModel();

    /**
     * Sets query operation
     *
     * @param string $operation
     *
     * @return $this
     */
    public function operation($operation);

    /**
     * Executes query
     * After execution query is reset
     *
     * @return mixed|null|void
     * @throws QueryException
     */
    public function execute();

    /**
     * Resets adapter
     *
     * @return $this
     */
    public function reset();
}