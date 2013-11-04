<?php
namespace moss\storage\builder;

interface BuilderInterface
{

    /**
     * Sets operation for builder
     *
     * @param string $operation
     *
     * @return $this
     */
    public function operation($operation);

    /**
     * Builds and returns query
     *
     * @return string
     */
    public function build();

    /**
     * Resets builder
     *
     * @return $this
     */
    public function reset();

    /**
     * Builds and returns query
     *
     * @return string
     */
    public function __toString();
}
