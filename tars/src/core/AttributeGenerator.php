<?php

namespace kuiper\tars\core;

use Laminas\Code\Generator\DocBlockGenerator;

class AttributeGenerator extends DocBlockGenerator
{
    public function __construct(string $sourceContent)
    {
        parent::__construct();
        $this->setSourceContent($sourceContent);
    }

    public function generate()
    {
        $indent = $this->getIndentation();
        $lines = explode(self::LINE_FEED, $this->getSourceContent());
        $output = '';
        foreach ($lines as $line) {
            $output .= $indent . $line . self::LINE_FEED;
        }
        return $output;
    }

}