<?php

declare(strict_types=1);

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * NOTE: This class is auto generated by Tars Generator (https://github.com/wenbinye/tars-generator).
 *
 * Do not edit the class manually.
 * Tars Generator version: 1.0
 */

namespace kuiper\tars\integration;

use kuiper\tars\annotation\TarsClient;
use kuiper\tars\annotation\TarsParameter;
use kuiper\tars\annotation\TarsReturnType;

/**
 * @TarsClient("tars.tarsregistry.QueryObj")
 */
interface QueryFServant
{
    /**
     * @tars-param id 对象名称
     *
     * @tars-return 返回所有该对象的活动endpoint列表
     *
     * @TarsParameter(name="id", type="string")
     * @TarsReturnType("vector<EndpointF>")
     *
     * @param string $id
     *
     * @return EndpointF[]
     */
    public function findObjectById(string $id): array;

    /**
     * @tars-param id         对象名称
     * @tars-param activeEp   存活endpoint列表
     * @tars-param inactiveEp 非存活endpoint列表
     * @tars-return 0-成功  others-失败
     *
     * @TarsParameter(name="id", type="string")
     * @TarsParameter(name="activeEp", type="vector<EndpointF>", out=true)
     * @TarsParameter(name="inactiveEp", type="vector<EndpointF>", out=true)
     * @TarsReturnType("int")
     *
     * @param string      $id
     * @param EndpointF[] $activeEp
     * @param EndpointF[] $inactiveEp
     *
     * @return int
     */
    public function findObjectById4Any(string $id, ?array &$activeEp, ?array &$inactiveEp): int;

    /**
     * @tars-param id         对象名称
     * @tars-param activeEp   存活endpoint列表
     * @tars-param inactiveEp 非存活endpoint列表
     * @tars-return 0-成功  others-失败
     *
     * @TarsParameter(name="id", type="string")
     * @TarsParameter(name="activeEp", type="vector<EndpointF>", out=true)
     * @TarsParameter(name="inactiveEp", type="vector<EndpointF>", out=true)
     * @TarsReturnType("int")
     *
     * @param string      $id
     * @param EndpointF[] $activeEp
     * @param EndpointF[] $inactiveEp
     *
     * @return int
     */
    public function findObjectById4All(string $id, ?array &$activeEp, ?array &$inactiveEp): int;

    /**
     * @tars-param id         对象名称
     * @tars-param activeEp   存活endpoint列表
     * @tars-param inactiveEp 非存活endpoint列表
     * @tars-return 0-成功  others-失败
     *
     * @TarsParameter(name="id", type="string")
     * @TarsParameter(name="activeEp", type="vector<EndpointF>", out=true)
     * @TarsParameter(name="inactiveEp", type="vector<EndpointF>", out=true)
     * @TarsReturnType("int")
     *
     * @param string      $id
     * @param EndpointF[] $activeEp
     * @param EndpointF[] $inactiveEp
     *
     * @return int
     */
    public function findObjectByIdInSameGroup(string $id, ?array &$activeEp, ?array &$inactiveEp): int;

    /**
     * @tars-param id         对象名称
     * @tars-param activeEp   存活endpoint列表
     * @tars-param inactiveEp 非存活endpoint列表
     * @tars-return 0-成功  others-失败
     *
     * @TarsParameter(name="id", type="string")
     * @TarsParameter(name="sStation", type="string")
     * @TarsParameter(name="activeEp", type="vector<EndpointF>", out=true)
     * @TarsParameter(name="inactiveEp", type="vector<EndpointF>", out=true)
     * @TarsReturnType("int")
     *
     * @param string      $id
     * @param string      $sStation
     * @param EndpointF[] $activeEp
     * @param EndpointF[] $inactiveEp
     *
     * @return int
     */
    public function findObjectByIdInSameStation(string $id, string $sStation, ?array &$activeEp, ?array &$inactiveEp): int;

    /**
     * @tars-param id         对象名称
     * @tars-param setId      set全称,格式为setname.setarea.setgroup
     * @tars-param activeEp   存活endpoint列表
     * @tars-param inactiveEp 非存活endpoint列表
     * @tars-return 0-成功  others-失败
     *
     * @TarsParameter(name="id", type="string")
     * @TarsParameter(name="setId", type="string")
     * @TarsParameter(name="activeEp", type="vector<EndpointF>", out=true)
     * @TarsParameter(name="inactiveEp", type="vector<EndpointF>", out=true)
     * @TarsReturnType("int")
     *
     * @param string      $id
     * @param string      $setId
     * @param EndpointF[] $activeEp
     * @param EndpointF[] $inactiveEp
     *
     * @return int
     */
    public function findObjectByIdInSameSet(string $id, string $setId, ?array &$activeEp, ?array &$inactiveEp): int;
}
