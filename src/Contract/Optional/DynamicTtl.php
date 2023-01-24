<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Contract\Optional;

interface DynamicTtl
{
    /**
     * Returns a dynamically generated TTL value.
     */
    public function getDynamicTtl(): int;
}
