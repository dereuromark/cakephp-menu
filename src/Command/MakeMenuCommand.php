<?php

declare(strict_types=1);

namespace Menu\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Utility\Inflector;

class MakeMenuCommand extends Command
{
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser
            ->addArgument('name', [
                'help' => 'UpperCamelCase menu name, for example Main.',
                'required' => true,
            ])
            ->addOption('force', [
                'boolean' => true,
                'default' => false,
                'help' => 'Overwrite an existing config file.',
            ]);
    }

    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $name = (string)$args->getArgument('name');
        if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $name)) {
            $io->abort('Menu name must be UpperCamelCase (letters and digits only, starting with a capital letter).');
        }
        $snake = Inflector::underscore($name);
        $configDir = $this->getConfigDir();
        $path = $configDir . DIRECTORY_SEPARATOR . 'menu_' . $snake . '.php';

        if (!is_dir($configDir) && !mkdir($configDir, 0775, true) && !is_dir($configDir)) {
            $io->abort(sprintf('Could not create config directory `%s`.', $configDir));
        }

        if (is_file($path) && !$args->getOption('force')) {
            $io->abort(sprintf('Config file `%s` already exists. Use --force to overwrite it.', $path));
        }

        if (file_put_contents($path, $this->buildTemplate($name, $snake)) === false) {
            $io->abort(sprintf('Could not write `%s`.', $path));
        }

        $io->out(sprintf('Created `%s`.', $path));
        $io->out(sprintf("Load it with Configure::load('menu_%s', 'default', true);", $snake));

        return static::CODE_SUCCESS;
    }

    protected function getConfigDir(): string
    {
        return ROOT . DIRECTORY_SEPARATOR . 'config';
    }

    protected function buildTemplate(string $name, string $snake): string
    {
        return <<<PHP
<?php
/**
 * Menu spec for "{$name}". Load this from your app/plugin bootstrap, e.g.:
 *     Configure::load('menu_{$snake}', 'default', true);
 *
 * The Menu helper auto-registers every entry under `Menu.menus` on initialize, so
 * `\$this->Menu->render('{$snake}')` works without any wiring.
 */

return [
    'Menu' => [
        'menus' => [
            '{$snake}' => [
                'attributes' => ['class' => 'nav'],
                'items' => [
                    ['label' => 'Home', 'link' => '/'],
                ],
            ],
        ],
    ],
];
PHP;
    }
}
