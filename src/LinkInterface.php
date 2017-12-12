<?php
declare(strict_types = 1);

namespace Cake\Menu;

interface LinkInterface
{

    /**
     * Set HTML attributes for the link
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setAttribute($name, $value);

    /**
     * Get all attributes as array
     *
     * @return array Attributes
     */
    public function getAttributes();

    /**
     * Returns whatever the implementation expects to build an URL from
     *
     * @return mixed Returns whatever the implementation expects to build an URL from
     */
    public function getRawUrl();

    /**
     * Get the URL
     *
     * @return string
     */
    public function getUrl();
}
