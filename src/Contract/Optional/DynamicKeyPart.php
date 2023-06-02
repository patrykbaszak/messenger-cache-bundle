<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Contract\Optional;

interface DynamicKeyPart
{
    /**
     * Returns a dynamically generated part of cache key.
     * Use it as a cache owner identifier.
     */
    public function getDynamicKeyPart(): string;
}
