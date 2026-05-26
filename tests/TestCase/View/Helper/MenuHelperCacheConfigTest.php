<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\View\Helper;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use InvalidArgumentException;
use Menu\MenuInterface;
use Menu\View\Helper\MenuHelper;

class MenuHelperCacheConfigTest extends TestCase
{
    protected function createHelper(ServerRequest $request): MenuHelper
    {
        return new MenuHelper(new View(
            request: $request,
            response: new Response(),
        ));
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::setConfig('menu_test', ['className' => 'Array']);
    }

    protected function tearDown(): void
    {
        Cache::clear('menu_test');
        Cache::drop('menu_test');
        Configure::delete('Menu.menus');
        parent::tearDown();
    }

    public function testRegisterWithCacheBuildsOnceThenReadsFromCache(): void
    {
        $calls = 0;
        $build = function (MenuInterface $menu) use (&$calls): void {
            $calls++;
            $menu->addItem('Home', '/home');
        };
        $options = ['cache' => ['key' => 'menu_main', 'config' => 'menu_test']];

        $this->createHelper(new ServerRequest())->register('main', $build, $options);
        $this->assertSame(1, $calls);

        // A fresh helper (simulating another request) loads from cache without rebuilding.
        $helper = $this->createHelper(new ServerRequest());
        $helper->register('main', $build, $options);

        $this->assertSame(1, $calls);
        $this->assertSame(
            '<ul><li><a href="/home">Home</a></li></ul>',
            $helper->render('main'),
        );
    }

    public function testCacheKeyDefaultsToMenuNameSoMenusDoNotCollide(): void
    {
        $options = ['cache' => ['config' => 'menu_test']]; // no explicit key

        $first = $this->createHelper(new ServerRequest());
        $first->register('alpha', static function (MenuInterface $m): void {
            $m->addItem('Alpha', '/alpha');
        }, $options);
        $first->register('beta', static function (MenuInterface $m): void {
            $m->addItem('Beta', '/beta');
        }, $options);

        // A fresh helper reads each menu from its own cache entry (keyed by menu name).
        $second = $this->createHelper(new ServerRequest());
        $second->register('alpha', static function (MenuInterface $m): void {
            $m->addItem('X', '/x');
        }, $options);
        $second->register('beta', static function (MenuInterface $m): void {
            $m->addItem('Y', '/y');
        }, $options);

        $this->assertSame('<ul><li><a href="/alpha">Alpha</a></li></ul>', $second->render('alpha'));
        $this->assertSame('<ul><li><a href="/beta">Beta</a></li></ul>', $second->render('beta'));
    }

    public function testBooleanCacheUsesMenuNameAsKey(): void
    {
        Cache::setConfig('default', ['className' => 'Array']);
        try {
            $first = $this->createHelper(new ServerRequest());
            $first->register('alpha', static function (MenuInterface $m): void {
                $m->addItem('Alpha', '/alpha');
            }, ['cache' => true]);
            $first->register('beta', static function (MenuInterface $m): void {
                $m->addItem('Beta', '/beta');
            }, ['cache' => true]);

            $second = $this->createHelper(new ServerRequest());
            $second->register('alpha', static function (MenuInterface $m): void {
                $m->addItem('X', '/x');
            }, ['cache' => true]);
            $second->register('beta', static function (MenuInterface $m): void {
                $m->addItem('Y', '/y');
            }, ['cache' => true]);

            $this->assertSame('<ul><li><a href="/alpha">Alpha</a></li></ul>', $second->render('alpha'));
            $this->assertSame('<ul><li><a href="/beta">Beta</a></li></ul>', $second->render('beta'));
        } finally {
            Cache::clear('default');
            Cache::drop('default');
        }
    }

    public function testRegisterWithCacheRebuildForcesRebuild(): void
    {
        $calls = 0;
        $build = function (MenuInterface $menu) use (&$calls): void {
            $calls++;
            $menu->addItem('Home', '/home');
        };
        $options = ['cache' => ['key' => 'menu_main', 'config' => 'menu_test']];

        $this->createHelper(new ServerRequest())->register('main', $build, $options);
        $this->createHelper(new ServerRequest())->register('main', $build, $options + ['rebuild' => true]);

        $this->assertSame(2, $calls);
    }

    public function testAutoLoadsMenusFromConfigure(): void
    {
        Configure::write('Menu.menus', [
            'main' => [
                'attributes' => ['class' => 'nav'],
                'items' => [
                    ['label' => 'Home', 'link' => '/home'],
                ],
            ],
        ]);

        $helper = $this->createHelper(new ServerRequest());

        $this->assertTrue($helper->has('main'));
        $this->assertSame(
            '<ul class="nav"><li><a href="/home">Home</a></li></ul>',
            $helper->render('main'),
        );
    }

    public function testRegisterOverridesConfiguredMenu(): void
    {
        Configure::write('Menu.menus', [
            'main' => ['items' => [['label' => 'Home', 'link' => '/home']]],
        ]);

        $helper = $this->createHelper(new ServerRequest());
        $helper->register('main', function (MenuInterface $menu): void {
            $menu->addItem('Extra', '/extra');
        });

        // An explicit registration runs its callback and overrides the configured menu of the same
        // name (the callback is never silently dropped).
        $this->assertSame(
            '<ul><li><a href="/extra">Extra</a></li></ul>',
            $helper->render('main'),
        );
    }

    public function testRegisterOverridesConfiguredMenuEvenAfterRender(): void
    {
        Configure::write('Menu.menus', [
            'main' => ['items' => [['label' => 'Home', 'link' => '/home']]],
        ]);

        $helper = $this->createHelper(new ServerRequest());
        // Materialize the configured menu first (inspect/render), then register over it.
        $helper->render('main');
        $helper->register('main', function (MenuInterface $menu): void {
            $menu->addItem('Extra', '/extra');
        });

        $this->assertSame(
            '<ul><li><a href="/extra">Extra</a></li></ul>',
            $helper->render('main'),
        );
    }

    public function testRemoveDeletesConfiguredMenu(): void
    {
        Configure::write('Menu.menus', [
            'main' => ['items' => [['label' => 'Home', 'link' => '/home']]],
        ]);

        $helper = $this->createHelper(new ServerRequest());
        $helper->render('main');
        $helper->remove('main');

        $this->assertFalse($helper->has('main'));
        $this->expectException(InvalidArgumentException::class);
        $helper->render('main');
    }

    public function testResetKeepsConfiguredMenusAvailable(): void
    {
        Configure::write('Menu.menus', [
            'main' => ['items' => [['label' => 'Home', 'link' => '/home']]],
        ]);

        $helper = $this->createHelper(new ServerRequest());
        $helper->render('main');
        $helper->reset();

        // Configured menus remain available after a reset (config is still present).
        $this->assertTrue($helper->has('main'));
        $this->assertSame(
            '<ul><li><a href="/home">Home</a></li></ul>',
            $helper->render('main'),
        );
    }
}
