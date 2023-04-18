<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Event;

use PBaszak\MessengerCacheBundle\Stamps\CacheItemHitStamp;
use PBaszak\MessengerCacheBundle\Stamps\CacheItemMissStamp;
use PBaszak\MessengerCacheBundle\Stamps\CacheItemTagsStamp;
use PBaszak\MessengerCacheBundle\Stamps\CacheRefreshTriggeredStamp;
use PBaszak\MessengerCacheBundle\Stamps\ForceCacheRefreshStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CacheEvent extends Event
{
    /**
     * @param StampInterface[] $stamps - origin stamps
     */
    public function __construct(
        private Envelope $envelope,
        private array $stamps = []
    ) {
    }

    public function getEnvelope(): Envelope
    {
        return $this->envelope;
    }

    /**
     * @param class-string<StampInterface>|null $stampFqcn
     *
     * @return StampInterface[]
     */
    public function getOriginStamps(?string $stampFqcn = null): array
    {
        if (!$stampFqcn) {
            return $this->stamps;
        }

        $output = [];
        foreach ($this->stamps as $stamp) {
            if ($stamp instanceof $stampFqcn) {
                $output[] = $stamp;
            }
        }

        return $output;
    }

    /**
     * @param class-string<StampInterface>|null $stampFqcn
     *
     * @return StampInterface[]|array<StampInterface[]>
     */
    public function getStamps(?string $stampFqcn = null): array
    {
        return $this->envelope->all($stampFqcn);
    }

    public function isHit(): bool
    {
        return (bool) $this->envelope->last(CacheItemHitStamp::class);
    }

    public function isMiss(): bool
    {
        return (bool) $this->envelope->last(CacheItemMissStamp::class);
    }

    public function isTriggeredRefresh(): bool
    {
        return (bool) $this->envelope->last(CacheRefreshTriggeredStamp::class);
    }

    public function isForcedRefresh(): bool
    {
        return (bool) $this->envelope->last(ForceCacheRefreshStamp::class);
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->envelope->last(CacheItemTagsStamp::class)?->tags ?? [];
    }

    public function getValue(): mixed
    {
        return $this->envelope->last(HandledStamp::class)?->getResult() ?? null;
    }
}
