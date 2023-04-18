<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Event;

use PBaszak\MessengerCacheBundle\Stamps\InvalidationResultsStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Contracts\EventDispatcher\Event;

class CacheInvalidationEvent extends Event
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

    /**
     * @return string[]
     */
    public function getInvalidatedTags(?string $pool = null): array
    {
        $tags = [];
        foreach ($this->envelope->all(InvalidationResultsStamp::class) as $stamp) {
            foreach ($stamp->results as $tag => $pools) {
                if (null === $pool || isset($pools[$pool])) {
                    $tags[] = $tag;
                }
            }
        }

        return array_unique($tags);
    }

    /**
     * @return array<string, mixed>
     */
    public function getInvalidationResults(?string $pool = null): array
    {
        $results = [];
        foreach ($this->envelope->all(InvalidationResultsStamp::class) as $stamp) {
            foreach ($stamp->results as $tag => $pools) {
                foreach ($pools as $poolAlias => $result) {
                    if (null === $pool || $poolAlias === $pool) {
                        $results[$poolAlias][$tag] = $result;
                    }
                }
            }
        }

        return $pool ? $results[$pool] : $results;
    }
}
