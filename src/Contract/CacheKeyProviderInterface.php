<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Contract;

interface CacheKeyProviderInterface
{
    /**
     * You should ignore the stamps, but perhaps in your individual case 
     * they matter, so they are always available in this method.
     */
    public function createKey(object $message, array $stamps = []): string;
}
