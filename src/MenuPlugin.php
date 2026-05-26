<?php

declare(strict_types=1);

namespace Menu;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Menu\Command\MenuGenerateCommand;

class MenuPlugin extends BasePlugin
{
    public function console(CommandCollection $commands): CommandCollection
    {
        return $commands->add('menu generate', MenuGenerateCommand::class);
    }
}
