<?php
namespace moss\storage\builder;

interface BuilderInterface
{
    /**
     * Sets table name
     *
     * @param string $table
     *
     * @return $this
     */
    public function table($table);

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