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
        private array $tags = [],
        private ?string $pool = null
    ) {
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getPool(): ?string
    {
        return $this->pool;
    }
}
