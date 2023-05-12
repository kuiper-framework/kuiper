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

namespace kuiper\cache\stash\driver;

use kuiper\cache\stash\DriverInterface;
use kuiper\cache\stash\InvalidArgumentException;

class Composite extends AbstractDriver
{
    /**
     * @var DriverInterface[]
     */
    private array $drivers;

    protected function setOptions(array $options = []): void
    {
        if (!isset($options['drivers'])) {
            throw new InvalidArgumentException('One or more secondary drivers are required.');
        }

        if (!is_array($options['drivers'])) {
            throw new InvalidArgumentException('Drivers option requires an array.');
        }

        if (count($options['drivers']) < 1) {
            throw new InvalidArgumentException('One or more secondary drivers are required.');
        }

        $this->drivers = [];
        foreach ($options['drivers'] as $driver) {
            if (!($driver instanceof DriverInterface)) {
                continue;
            }
            $this->drivers[] = $driver;
        }

        if (count($this->drivers) < 1) {
            throw new InvalidArgumentException('None of the secondary drivers can be enabled.');
        }
    }

    public function getData(string $key): array
    {
        $failedDrivers = [];
        $return = false;
        foreach ($this->drivers as $driver) {
            if ($return = $driver->getData($key)) {
                $failedDrivers = array_reverse($failedDrivers);
                /* @var DriverInterface[] $failedDrivers */

                foreach ($failedDrivers as $failedDriver) {
                    $failedDriver->storeData($key, $return['data'], $return['expiration']);
                }

                break;
            }

            $failedDrivers[] = $driver;
        }

        return $return;
    }

    public function storeData(string $key, mixed $data, int $expiration): bool
    {
        return $this->actOnAll('storeData', [$key, $data, $expiration]);
    }

    public function clear(string $key = null): bool
    {
        return $this->actOnAll('clear', [$key]);
    }

    public function purge(): bool
    {
        return $this->actOnAll('purge');
    }

    /**
     * This function runs the suggested action on all drivers in the reverse order, passing arguments when called for.
     *
     * @param string $action purge|clear|storeData
     * @param array  $args
     *
     * @return bool
     */
    protected function actOnAll(string $action, array $args = [])
    {
        $drivers = array_reverse($this->drivers);
        /* @var DriverInterface[] $drivers */

        $return = true;
        $results = false;
        foreach ($drivers as $driver) {
            switch ($action) {
                case 'purge':
                    $results = $driver->purge();
                    break;
                case 'clear':
                    $results = $driver->clear($args[0]);
                    break;
                case 'storeData':
                    $results = $driver->storeData($args[0], $args[1], $args[2]);
                    break;
            }
            $return = $return && $results;
        }

        return $return;
    }
}
