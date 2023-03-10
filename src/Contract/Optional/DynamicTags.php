<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Contract\Optional;

interface DynamicTags
{
    /**
     * Returns a dynamically generated tags.
     *
     * @return string[]
     */
    public function getDynamicTags(): array;
}
