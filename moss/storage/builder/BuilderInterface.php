<?php
namespace moss\storage\builder;

interface BuilderInterface
{
    /**
     * Sets container name
     *
     * @param string $container
     *
     * @return $this
     */
    public function container($container);

    /**
     * Sets operation for builder
     *
     * @param string $operation
     *
     * @return $this
     */
    public function operation($operation);

    /**
     * Builds query string
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
     * Casts query to string (builds it)
     *
     * @return string
     */
    public function __toString();
} 