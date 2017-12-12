<?php
namespace Cake\Menu\Resolver;

use Cake\Menu\ItemInterface;
use Psr\Http\Message\RequestInterface;

class Psr7UrlResolver implements ResolverInterface
{

    /**
     * @var \Psr\Http\Message\RequestInterface
     */
    protected $_request;

    /**
     * @param \Psr\Http\Message\RequestInterface $request
     */
    public function __construct(RequestInterface $request, array $options = [])
    {
        $this->_request = $request;
    }

    public function resolve(ItemInterface $item)
    {
        if ((string) $this->_request->getUri() === $item->getLink()->getUrl()) {
            $item->setActive(true);
        }
    }

}
