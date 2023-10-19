<?php

declare(strict_types=1);

namespace kuiper\metrics\registry;

use kuiper\metrics\metric\MetricId;

abstract class MetricFilter implements MetricFilterInterface
{
    public function accept(MetricId $metricId): bool
    {
        return true;
    }

    public function map(MetricId $metricId): MetricId
    {
        return $metricId;
    }

    public static function commonTags(array $tags): MetricFilterInterface
    {
        return new class($tags) extends MetricFilter {
            public function __construct(private readonly array $tags)
            {
            }

            public function map(MetricId $metricId): MetricId
            {
                return $metricId->withAddedTags($this->tags);
            }
        };
    }

    public static function ignoreTags(array $tagKeys): MetricFilterInterface
    {
        return new class($tagKeys) extends MetricFilter {
            public function __construct(private readonly array $tagKeys)
            {
            }

            public function map(MetricId $metricId): MetricId
            {
                return $metricId->withoutTags($this->tagKeys);
            }
        };
    }

    public static function renameTag(string $metricNamePrefix, string $fromTagKey, string $toTagKey): MetricFilterInterface
    {
        return new class($metricNamePrefix, $fromTagKey, $toTagKey) extends MetricFilter {
            public function __construct(
                private readonly string $metricNamePrefix,
                private readonly string $fromTagKey,
                private readonly string $toTagKey
            ) {
            }

            public function map(MetricId $metricId): MetricId
            {
                if (str_starts_with($metricId->getName(), $this->metricNamePrefix)) {
                    $tags = $metricId->getTags();
                    if (isset($tags[$this->fromTagKey])) {
                        $tags[$this->toTagKey] = $tags[$this->fromTagKey];
                        unset($tags[$this->fromTagKey]);

                        return $metricId->withTags($tags);
                    }
                }

                return parent::map($metricId);
            }
        };
    }

    public static function denyNameStartsWith(string $prefix): MetricFilterInterface
    {
        return new class($prefix) extends MetricFilter {
            public function __construct(private readonly string $prefix)
            {
            }

            public function accept(MetricId $metricId): bool
            {
                return !str_starts_with($metricId->getName(), $this->prefix);
            }
        };
    }

    public static function denyNameMatch(string $pattern): MetricFilterInterface
    {
        return new class($pattern) extends MetricFilter {
            public function __construct(private readonly string $pattern)
            {
            }

            public function accept(MetricId $metricId): bool
            {
                return !preg_match($this->pattern, $metricId->getName());
            }
        };
    }
}
