<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query;

class GetStrings
{
    public function __construct(
        public readonly int $stringLength = 10,
        public readonly int $numberOfStrings = 10,
    ) {
    }
}
