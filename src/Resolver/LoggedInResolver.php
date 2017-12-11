<?php
namespace Cake\Menu\Resolver;

use Cake\Menu\ItemInterface;
use Cake\Menu\StatusResolverInterface;

class LoggedInResolver implements ResolverInterface
{

    protected $_user;

    public function __construct($user)
    {
        $this->_user = $user;
    }

    public function resolve(ItemInterface $item)
    {
        if (!empty($user)) {
            $item->setVisibility(true);
        }
    }
}
