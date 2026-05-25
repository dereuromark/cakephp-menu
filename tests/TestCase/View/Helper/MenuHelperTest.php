<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\View\Helper;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Menu\Menu;
use Menu\View\Helper\MenuHelper;

class MenuHelperTest extends TestCase
{
    public function testRender(): void
    {
        $menuHelper = new MenuHelper(new View(
            request: new ServerRequest(),
            response: new Response(),
        ));
        $menu = Menu::create();
        $menu->addItem('First', '/x');
        $menu->addItem('Second', '/y');

        $html = $menuHelper->render($menu);

        $this->assertSame(
            '<ul><li><a href="/x">First</a></li><li><a href="/y">Second</a></li></ul>',
            $html,
        );
    }
}
