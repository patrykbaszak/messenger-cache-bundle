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
        private Cacheable $message,
        private array $stamps = []
    ) {
    }

    public function getMessage(): Cacheable
    {
        return $this->message;
    }

    /**
     * @return StampInterface[]
     */
    public function getStamps(): array
    {
        return $this->stamps;
    }
}
