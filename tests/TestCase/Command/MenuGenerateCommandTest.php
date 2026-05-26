<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Command;

use Cake\Console\CommandCollection;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\ConsoleApplicationInterface;
use Cake\TestSuite\TestCase;
use Menu\Command\MenuGenerateCommand;

class MenuGenerateCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected string $configDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configDir = ROOT . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'menu_command_test';
    }

    protected function tearDown(): void
    {
        if (is_dir($this->configDir)) {
            $files = glob($this->configDir . DIRECTORY_SEPARATOR . '*');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            rmdir($this->configDir);
        }

        parent::tearDown();
    }

    protected function createApp(): ConsoleApplicationInterface
    {
        $configDir = $this->configDir;

        return new class ($configDir) implements ConsoleApplicationInterface {
            public function __construct(protected string $configDir)
            {
            }

            public function bootstrap(): void
            {
            }

            public function console(CommandCollection $commands): CommandCollection
            {
                $command = new class ($this->configDir) extends MenuGenerateCommand {
                    public function __construct(protected string $configDir)
                    {
                        parent::__construct();
                    }

                    protected function getConfigDir(): string
                    {
                        return $this->configDir;
                    }
                };

                return $commands->add('menu generate', $command);
            }
        };
    }

    public function testCreatesFileWithExpectedContent(): void
    {
        $this->exec('menu generate Main');

        $path = $this->configDir . DIRECTORY_SEPARATOR . 'menu_main.php';
        $this->assertExitSuccess();
        $this->assertFileExists($path);
        $this->assertStringContainsString("'main' => [", (string)file_get_contents($path));
        $this->assertStringContainsString("'class' => 'nav'", (string)file_get_contents($path));
    }

    public function testRefusesToOverwriteWithoutForce(): void
    {
        mkdir($this->configDir, 0775, true);
        file_put_contents($this->configDir . DIRECTORY_SEPARATOR . 'menu_main.php', 'old');

        $this->exec('menu generate Main');

        $this->assertExitError();
        $this->assertErrorContains('already exists');
        $this->assertSame('old', file_get_contents($this->configDir . DIRECTORY_SEPARATOR . 'menu_main.php'));
    }

    public function testRejectsInvalidName(): void
    {
        $this->exec('menu generate Admin/Menu');

        $this->assertExitError();
        $this->assertErrorContains('UpperCamelCase');
        $this->assertFileDoesNotExist($this->configDir . DIRECTORY_SEPARATOR . 'menu_admin/menu.php');
    }

    public function testForceOverwrites(): void
    {
        mkdir($this->configDir, 0775, true);
        file_put_contents($this->configDir . DIRECTORY_SEPARATOR . 'menu_main.php', 'old');

        $this->exec('menu generate Main --force');

        $path = $this->configDir . DIRECTORY_SEPARATOR . 'menu_main.php';
        $this->assertExitSuccess();
        $this->assertStringContainsString("'Home'", (string)file_get_contents($path));
        $this->assertNotSame('old', file_get_contents($path));
    }
}
