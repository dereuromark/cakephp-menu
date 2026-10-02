<?php

declare(strict_types=1);

namespace CakeMenu;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use CakeMenu\Command\MenuGenerateCommand;

class CakeMenuPlugin extends BasePlugin
{
    public function console(CommandCollection $commands): CommandCollection
    {
        return $commands->add('menu generate', MenuGenerateCommand::class);
    }
}
