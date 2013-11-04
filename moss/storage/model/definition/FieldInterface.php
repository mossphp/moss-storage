<?php
namespace moss\storage\model\definition;

/**
 * Interface for Field definition for entity model
 *
 * @package moss storage
 * @author  Michal Wachowski <wachowski.michal@gmail.com>
 */
interface FieldInterface
{
    /**
     * Returns relation name in entity
     *
     * @return string
     */
    public function name();

    /**
     * Returns relation type
     *
     * @return string
     */
    public function type();

    /**
     * Returns field container mapping or null when no mapping
     *
     * @return null|string
     */
    public function mapping();

    /**
     * Returns attribute value or null if not set
     *
     * @param string $attribute
     *
     * @return mixed
     */
    public function attribute($attribute);

    /**
     * Returns array containing field attributes
     *
     * @return array
     */
    public function attributes();
}
