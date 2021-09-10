<?php

declare(strict_types=1);

namespace kuiper\swoole;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServerStopCommand extends Command
{
    /**
     * @var ServerManager
     */
    private $serverManager;

    /**
     * ServerCommand constructor.
     */
    public function __construct(ServerManager $serverManager)
    {
        parent::__construct();
        $this->serverManager = $serverManager;
    }

    protected function configure(): void
    {
        $this->setName('stop');
        $this->setDescription('Stop the server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->serverManager->stop();

        return 0;
    }
}
