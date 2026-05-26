<?php
/**
 * Example configuration for dereuromark/cakephp-menu.
 *
 * Copy the keys you need into your application's config (e.g. `config/app_local.php`,
 * `config/app.php`, or a dedicated `config/menu.php` loaded via `Configure::load('menu')`).
 */

return [
    /*
     * Menus declared in configuration. Each entry is keyed by menu name and is a
     * `Menu::fromArray()` spec. The Menu helper auto-registers these on initialize, so they are
     * renderable without any wiring:
     *
     *     echo $this->Menu->render('main');
     *
     * An explicit `$this->Menu->create('main')` / `register('main', ...)` in code overrides the
     * configured menu of the same name.
     */
    'Menu' => [
        'menus' => [
            'main' => [
                'attributes' => ['class' => 'nav'],
                'items' => [
                    ['label' => 'Home', 'link' => '/'],
                    [
                        'label' => 'Articles',
                        'link' => ['controller' => 'Articles', 'action' => 'index'],
                        'submenu' => [
                            'items' => [
                                ['label' => 'Latest', 'link' => ['controller' => 'Articles', 'action' => 'index', '?' => ['sort' => 'created']]],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
