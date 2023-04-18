<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Message;

class InvalidateAsync
{
    /**
     * @param string[]    $tags you can declare tags to invalidate
     * @param string|null $pool if `null` all TagAwareAdapters are used
     */
    public function __construct(
        public readonly array $tags = [],
        public readonly ?string $pool = null
    ) {
    }
}
