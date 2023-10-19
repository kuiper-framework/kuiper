<?php

declare(strict_types=1);

namespace kuiper\metrics\metric;

use kuiper\helper\Arrays;
use Stringable;

class MetricId implements Stringable
{
    private MetricType $type;
    private string $name;

    private array $tags;

    public function __construct(MetricType $type, string $name, array $tags = [])
    {
        $this->type = $type;
        $this->name = $name;
        $this->tags = Arrays::filter($tags);
    }

    public function getType(): MetricType
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function withTags(array $tags): self
    {
        return new self($this->type, $this->name, $tags);
    }

    public function withAddedTags(array $tags): self
    {
        return new self($this->type, $this->name, array_merge($this->tags, $tags));
    }

    public function withTag(string $name, string $value): self
    {
        return $this->withAddedTags([$name => $value]);
    }

    public function withoutTags($tagKeys): self
    {
        $tags = $this->tags;
        foreach ($tagKeys as $tagKey) {
            unset($tags[$tagKey]);
        }

        return new self($this->type, $this->name, $tags);
    }

    private function getTagsString(): string
    {
        return $this->tags ? '{'.implode(',', array_map(function ($k, $v) {
            return sprintf('%s="%s"', $k, addslashes($v));
        }, array_keys($this->tags), $this->tags)).'}' : '';
    }

    public function __toString(): string
    {
        return $this->name.$this->getTagsString();
    }
}
