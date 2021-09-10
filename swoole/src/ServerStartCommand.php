<?php

declare(strict_types=1);

namespace kuiper\swoole;

use kuiper\swoole\server\ServerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServerStartCommand extends Command
{
    /**
     * @var ServerInterface
     */
    private $server;

    /**
     * ServerCommand constructor.
     */
    public function __construct(ServerInterface $server)
    {
        parent::__construct();
        $this->server = $server;
    }

    protected function configure(): void
    {
        $this->setName('start');
        $this->setDescription('Start the server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->server->start();

        return 0;
    }
}
