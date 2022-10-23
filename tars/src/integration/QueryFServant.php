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

/**
 * NOTE: This class is auto generated by Tars Generator (https://github.com/wenbinye/tars-generator).
 *
 * Do not edit the class manually.
 * Tars Generator version: 0.6
 */

namespace kuiper\tars\integration;

use kuiper\tars\attribute\TarsClient;
use kuiper\tars\attribute\TarsParameter;
use kuiper\tars\attribute\TarsReturnType;

#[TarsClient('tars.tarsregistry.QueryObj')]
interface QueryFServant
{
    /**
     * @tars-param id 对象名称
     *
     * @tars-return 返回所有该对象的活动endpoint列表
     */
    #[TarsReturnType('vector<EndpointF>')]
    public function findObjectById(
        #[TarsParameter(type: 'string')] string $id
    ): array;

    /**
     * @tars-param id         对象名称
     * @tars-param activeEp   存活endpoint列表
     * @tars-param inactiveEp 非存活endpoint列表
     * @tars-return 0-成功  others-失败
     */
    #[TarsReturnType('int')]
    public function findObjectById4Any(
        #[TarsParameter(type: 'string')] string $id,
        #[TarsParameter(type: 'vector<EndpointF>')] ?array &$activeEp,
        #[TarsParameter(type: 'vector<EndpointF>')] ?array &$inactiveEp
    ): int;

    /**
     * @tars-param id         对象名称
     * @tars-param activeEp   存活endpoint列表
     * @tars-param inactiveEp 非存活endpoint列表
     * @tars-return 0-成功  others-失败
     */
    #[TarsReturnType('int')]
    public function findObjectById4All(
        #[TarsParameter(type: 'string')] string $id,
        #[TarsParameter(type: 'vector<EndpointF>')] ?array &$activeEp,
        #[TarsParameter(type: 'vector<EndpointF>')] ?array &$inactiveEp
    ): int;

    /**
     * @tars-param id         对象名称
     * @tars-param activeEp   存活endpoint列表
     * @tars-param inactiveEp 非存活endpoint列表
     * @tars-return 0-成功  others-失败
     */
    #[TarsReturnType('int')]
    public function findObjectByIdInSameGroup(
        #[TarsParameter(type: 'string')] string $id,
        #[TarsParameter(type: 'vector<EndpointF>')] ?array &$activeEp,
        #[TarsParameter(type: 'vector<EndpointF>')] ?array &$inactiveEp
    ): int;

    /**
     * @tars-param id         对象名称
     * @tars-param activeEp   存活endpoint列表
     * @tars-param inactiveEp 非存活endpoint列表
     * @tars-return 0-成功  others-失败
     */
    #[TarsReturnType('int')]
    public function findObjectByIdInSameStation(
        #[TarsParameter(type: 'string')] string $id,
        #[TarsParameter(type: 'string')] string $sStation,
        #[TarsParameter(type: 'vector<EndpointF>')] ?array &$activeEp,
        #[TarsParameter(type: 'vector<EndpointF>')] ?array &$inactiveEp
    ): int;

    /**
     * @tars-param id         对象名称
     * @tars-param setId      set全称,格式为setname.setarea.setgroup
     * @tars-param activeEp   存活endpoint列表
     * @tars-param inactiveEp 非存活endpoint列表
     * @tars-return 0-成功  others-失败
     */
    #[TarsReturnType('int')]
    public function findObjectByIdInSameSet(
        #[TarsParameter(type: 'string')] string $id,
        #[TarsParameter(type: 'string')] string $setId,
        #[TarsParameter(type: 'vector<EndpointF>')] ?array &$activeEp,
        #[TarsParameter(type: 'vector<EndpointF>')] ?array &$inactiveEp
    ): int;
}
