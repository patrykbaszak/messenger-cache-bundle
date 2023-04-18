<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Stamps;

use Symfony\Component\Messenger\Stamp\StampInterface;

class CacheItemTagsStamp implements StampInterface
{
    /**
     * @var string[]
     */
    public array $tags = [];

    /**
     * @param string[] $tags
     */
    public function __construct(
        array $tags,
    ) {
        foreach ($tags as $tag) {
            $this->tags[] = (string) $tag;
        }
    }
}
