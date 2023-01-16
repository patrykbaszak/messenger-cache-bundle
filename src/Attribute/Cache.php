<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Cache
{
    public function __construct(
        public readonly ?int $ttl = 3600,
        public readonly ?int $refreshAfter = null,
        public readonly ?string $adapter = null,
        public readonly ?string $group = null,
        public readonly array $tags = [],
    ) {}
}
