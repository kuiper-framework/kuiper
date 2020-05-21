<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

interface StrategyInterface
{
    public function setDbRule(RuleInterface $rule);

    public function setTableRule(RuleInterface $rule);

    /**
     * @param string $table
     *
     * @return string
     */
    public function getTable(array $fields, $table);

    /**
     * @return int
     */
    public function getDb(array $fields);
}
