<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\helper\Arrays;
use kuiper\helper\Text;
use kuiper\swoole\Composer;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;

class PackageBuilder
{
    public static function run(): void
    {
        $builder = new self();
        $serverName = $_SERVER['argv'][2] ?? '';
        if (Text::startsWith($serverName, '-')) {
            $serverName = null;
        }
        $builder->build($serverName);
    }

    public function build(?string $serverName): void
    {
        $output = new ConsoleOutput();
        $composerJson = Composer::detect();
        $basePath = dirname($composerJson);
        $config = $this->loadConfig($serverName, $composerJson);
        $filesystem = new Filesystem();

        $tempFile = tempnam(sys_get_temp_dir(), 'tars-build');
        @unlink($tempFile);
        $dir = $tempFile.'/'.$config->getServerName();
        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException("Cannot create temporary directory $dir");
        }
        $basePathLen = strlen($basePath);
        $n = 0;
        foreach ($config->getFinders() as $finder) {
            foreach ($finder as $fileInfo) {
                /** @var \SplFileInfo $fileInfo */
                $file = (string) $fileInfo;
                $relPath = substr($file, $basePathLen);
                // error_log("copy $relPath to ${dir}$relPath");
                ++$n;
                if (0 === $n % 100) {
                    $output->writeln("copy $n files to $dir");
                }
                $filesystem->copy($file, $dir.$relPath);
            }
        }
        // 检查 index.php 是否存在
        if (!file_exists($dir.'/src/index.php')) {
            throw new \RuntimeException("the entrance file $basePath/src/index.php does not exist: $dir");
        }

        //打包
        $tgzFile = $basePath.'/'.sprintf('%s_%s.tar.gz', $config->getServerName(), date('YmdHis'));
        $phar = new \PharData($tgzFile);
        $phar->compress(\Phar::GZ);
        $phar->buildFromDirectory($tempFile);
        $filesystem->remove($tempFile);

        $output->writeln("<info>create package $tgzFile</info>");
    }

    private function loadConfig(?string $serverName, string $composerJson): PackageConfig
    {
        $json = Composer::getJson($composerJson);
        $options = $json['extra']['tars'] ?? [];

        $options = Arrays::mapKeys($options, static function ($key): string {
            return str_replace('-', '_', Text::snakeCase($key, '_'));
        });
        if (Text::isNotEmpty($serverName)) {
            $options['server_name'] = $serverName;
        }

        return new PackageConfig(dirname($composerJson), $options);
    }
}
