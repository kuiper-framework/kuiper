<?php

declare(strict_types=1);

namespace kuiper\swoole;

use kuiper\swoole\server\ServerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServerCommand extends Command
{
    public const NAME = 'server';

    /**
     * @var ServerInterface
     */
    private $server;

    /**
     * @var ServerManager
     */
    private $serverManager;

    /**
     * ServerCommand constructor.
     */
    public function __construct(ServerInterface $server, ServerManager $serverManager)
    {
        parent::__construct();
        $this->server = $server;
        $this->serverManager = $serverManager;
    }

    protected function configure(): void
    {
        $this->setName(self::NAME);
        $this->setDescription('Start, stop or reload the server');
        $this->addOption('start', null, InputOption::VALUE_NONE, 'start the server');
        $this->addOption('stop', null, InputOption::VALUE_NONE, 'stop the server');
        $this->addOption('reload', null, InputOption::VALUE_NONE, 'reload the server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('stop')) {
            $this->stop($input, $output);
        } elseif ($input->getOption('reload')) {
            $this->reload($input, $output);
        } else {
            $this->start($input, $output);
        }

        return 0;
    }

    protected function start(InputInterface $input, OutputInterface $output): void
    {
        $this->server->start();
    }

    protected function reload(InputInterface $input, OutputInterface $output): void
    {
        $this->serverManager->reload();
    }

    protected function stop(InputInterface $input, OutputInterface $output): void
    {
        $this->serverManager->stop();
    }
}
