<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Application\DTO;

class StringsCollection
{
    public readonly int $count;

    /** @param Strings[] $items */
    public function __construct(
        public readonly array $items
    ) {
        $this->count = count($items);
    }
}
