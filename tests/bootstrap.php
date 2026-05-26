<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Routing\Router;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

define('ROOT', dirname(__DIR__));
define('TMP', ROOT . DS . 'tmp' . DS);
define('CACHE', TMP . 'cache' . DS);

if (!is_dir(CACHE)) {
    mkdir(CACHE, 0775, true);
}

require ROOT . '/vendor/autoload.php';
require ROOT . '/vendor/cakephp/cakephp/config/bootstrap.php';

Configure::write('App', [
    'namespace' => 'Menu\TestApp',
    'encoding' => 'UTF-8',
]);
Configure::write('debug', true);
Configure::write('App.encoding', 'UTF-8');

Cache::setConfig([
    '_cake_core_' => [
        'className' => 'File',
        'prefix' => 'menu_cake_core_',
        'path' => CACHE . 'persistent/',
        'serialize' => true,
        'duration' => '+10 seconds',
    ],
    '_cake_translations_' => [
        'className' => 'File',
        'prefix' => 'menu_cake_translations_',
        'path' => CACHE . 'translations/',
        'serialize' => true,
        'duration' => '+10 seconds',
    ],
    '_cake_model_' => [
        'className' => 'File',
        'prefix' => 'menu_cake_model_',
        'path' => CACHE . 'models/',
        'serialize' => true,
        'duration' => '+10 seconds',
    ],
]);

ConnectionManager::setConfig('test', [
    'className' => 'Cake\Database\Connection',
    'driver' => 'Cake\Database\Driver\Sqlite',
    'database' => ':memory:',
    'quoteIdentifiers' => true,
    'cacheMetadata' => true,
]);

Router::reload();
require __DIR__ . '/config/routes.php';
