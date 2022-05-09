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

namespace kuiper\tars\server;

use kuiper\helper\Text;
use kuiper\swoole\coroutine\Coroutine;
use kuiper\swoole\server\ServerInterface;
use kuiper\swoole\server\SwooleServer;
use kuiper\tars\TarsApplication;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServerStartCommand extends AbstractServerCommand
{
    protected const TAG = '['.__CLASS__.'] ';

    public const COMMAND_NAME = 'start';

    public function __construct(private readonly ServerInterface $server, private readonly ServerProperties $serverProperties)
    {
        parent::__construct(self::COMMAND_NAME);
    }

    protected function configure(): void
    {
        $this->setDescription('start php server');
        $this->addOption('server', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->serverProperties->isExternalMode() || false !== $input->getOption('server')) {
            if ($this->server instanceof SwooleServer) {
                Coroutine::enable();
            }
            $this->server->start();
        } else {
            $this->writePidFile();
            $this->startService();
        }

        return 0;
    }

    private function writePidFile(): void
    {
        file_put_contents($this->serverProperties->getServerPidFile(), getmypid());
    }

    private function startService(): void
    {
        $confPath = $this->serverProperties->getSupervisorConfPath();
        if (null === $confPath || !is_dir($confPath)) {
            throw new RuntimeException('tars.application.server.supervisor_conf_path cannot be empty when start_mode is external');
        }
        $serviceName = $this->serverProperties->getServerName();
        $configFile = $confPath.'/'.$serviceName.$this->serverProperties->getSupervisorConfExtension();
        $success = $this->withFileLock($configFile, function () use ($serviceName, $configFile): void {
            if (file_exists($configFile.'.disabled')) {
                @unlink($configFile.'.disabled');
            }
            $env = $this->serverProperties->getEnv() ?? '';
            if (Text::isNotEmpty($this->serverProperties->getEmalloc())) {
                $env = (!empty($env) ? ',' : '')
                    .sprintf('USE_ZEND_ALLOC="0",LD_PRELOAD="%s"', $this->serverProperties->getEmalloc());
            }
            $configContent = strtr('[program:{server_name}]
directory={cwd}
environment={env}
command={php} {script_file} --config={conf_file} start --server
stdout_logfile={log_file}
redirect_stderr=true
', [
                '{cwd}' => getcwd(),
                '{server_name}' => $serviceName,
                '{php}' => $this->serverProperties->getPhp() ?? PHP_BINARY,
                '{env}' => $env,
                '{script_file}' => realpath($_SERVER['SCRIPT_FILENAME']),
                '{log_file}' => $this->serverProperties->getAppLogPath().'/'.$serviceName.'.log',
                '{conf_file}' => realpath(TarsApplication::getInstance()->getConfigFile()),
            ]);
            $supervisorctl = $this->serverProperties->getSupervisorctl() ?? 'supervisorctl';
            if (!file_exists($configFile) || (file_get_contents($configFile) !== $configContent)) {
                $this->logger->info(static::TAG."create supervisor config $configFile");
                file_put_contents($configFile, $configContent);
                system("$supervisorctl reread", $ret);
                $this->logger->info(static::TAG."reload $configFile with exit code $ret");
                system("$supervisorctl add $serviceName", $ret);
            } else {
                system("$supervisorctl start ".$serviceName, $ret);
            }
            $this->logger->info(static::TAG."start $serviceName with exit code $ret");
        });
        if ($success) {
            pcntl_exec('/bin/sleep', [2147000000 + $this->server->getServerConfig()->getPort()->getPort()]);
        }
    }
}
