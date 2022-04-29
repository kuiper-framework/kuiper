<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\swoole;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServerStopCommand extends Command
{
    /**
     * ServerCommand constructor.
     */
    public function __construct(private readonly ServerManager $serverManager)
    {
        parent::__construct();
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
