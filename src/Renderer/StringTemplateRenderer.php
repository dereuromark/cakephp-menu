<?php
declare(strict_types = 1);

namespace Cake\Menu\Renderer;

use Cake\Menu\ItemInterface;
use Cake\View\StringTemplateTrait;

class StringTemplateRenderer
{

    use StringTemplateTrait;

    protected $_templates = [
        'menuWrapper' => '<ul>{{items}}</ul>',
        'item' => '<li {{attributes}}>{{item}}</li>',
        'link' => '<a {{attributes}}>{{title}}</a>'
    ];

    public function render()
    {
    }

    public function renderItem(ItemInterface $item)
    {
        $attributes = $item->getLink()->getAttributes();
        $href = $item->getLink()->getUrl();
        $title = $item->getTitle();

        if ($item instanceof ItemSelfRenderer) {
            return $item->render();
        }

        return $this->templater()->format('link', [
            $attributes => $item->getLink()->getAttributes(),
            $href => $item->getLink()->getUrl(),
            $title => $item->getTitle()
        ]);
    }
}
