<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Message;

use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use Symfony\Component\Messenger\Stamp\StampInterface;

class RefreshAsync
{
    /**
     * @param StampInterface[] $stamps
     */
    public function __construct(
        public readonly Cacheable $message,
        public readonly array $stamps = []
    ) {
    }
}
