<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class InvalidationResultsStamp implements StampInterface
{
    /**
     * @param array<string,array<string, bool>> $results
     */
    public function __construct(
        public readonly array $results,
    ) {
    }
}
