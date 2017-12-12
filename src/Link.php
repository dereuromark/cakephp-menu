<?php
declare(strict_types = 1);

namespace Cake\Menu;

use Cake\Routing\Router;

class Link implements LinkInterface
{

    protected $_attributes = [];
    protected $_url;
    protected $_title;

    public function setUrl($url)
    {
        $this->_url = $url;

        return $this;
    }

    public function setTitle($title)
    {
        $this->_title = $title;

        return $this;
    }

    public function setAttribute($name, $value)
    {
        $this->_attributes[$name] = $value;

        return $this;
    }

    public function getAttributes()
    {
        return $this->_attributes;
    }

    public function getRawUrl()
    {
        return $this->_url;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        $rawUrl = $this->getRawUrl();
        if (is_string($rawUrl)) {
            return $rawUrl;
        }

        return $this->_builder($rawUrl);
    }

    /**
     * @return string
     */
    protected function _builder($rawUrl)
    {
        if (is_string($rawUrl)) {
            return $rawUrl;
        }
        return Router::url($rawUrl);
    }
}
