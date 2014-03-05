<?php

/*
 * This file is part of the Storage package
 *
 * (c) Michal Wachowski <wachowski.michal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Moss\Storage\Builder;

/**
 * Builder interface
 *
 * @author Michal Wachowski <wachowski.michal@gmail.com>
 * @package Moss\Storage\Builder\MySQL
 */
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
