<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query;

class GetString
{
    public function __construct(
        public int $length = 10,
    ) {
    }
}
