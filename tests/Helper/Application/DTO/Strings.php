<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Application\DTO;

class Strings
{
    public function __construct(
        public readonly array $strings
    ) {
    }
}
