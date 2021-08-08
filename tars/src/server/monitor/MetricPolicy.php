<?php

declare(strict_types=1);

namespace kuiper\tars\server\monitor;

/**
 * @see https://tarscloud.github.io/TarsDocs/dev/tars.js/tars-monitor.html
 * POLICY.Max：统计最大值
 * POLICY.Min：统计最小值
 * POLICY.Count：统计一共有多少个数据
 * POLICY.Sum：将所有数据进行相加
 * POLICY.Avg：计算数据的平均值
 * POLICY.Distr：分区间统计
 */
class MetricPolicy
{
    public const MAX = 'Max';
    public const MIN = 'Min';
    public const COUNT = 'Count';
    public const SUM = 'Sum';
    public const AVG = 'Avg';
    public const DISTR = 'Distr';
}
