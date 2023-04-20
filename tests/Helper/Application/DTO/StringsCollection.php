<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Application\DTO;

class StringsCollection
{
    public int $count;

    /** @param Strings[] $items */
    public function __construct(
        public array $items
    ) {
        $this->count = count($items);
    }
}
