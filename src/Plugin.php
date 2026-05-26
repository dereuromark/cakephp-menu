<?php

declare(strict_types=1);

namespace Menu;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Menu\Command\MakeMenuCommand;

class Plugin extends BasePlugin
{
    public function console(CommandCollection $commands): CommandCollection
    {
        return $commands->add('make_menu', MakeMenuCommand::class);
    }
}
