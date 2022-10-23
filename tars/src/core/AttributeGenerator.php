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
            $output .= $indent.$line.self::LINE_FEED;
        }

        return $output;
    }
}
