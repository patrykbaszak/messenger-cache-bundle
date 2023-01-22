<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Contract\Optional;

interface UniqueHash
{
    /**
     * Returns hash of given `$message` instance. You can use it
     * instead of `getHashableInstance()` and You have to use it
     * when You want cache output from anonymous class instance.
     */
    public function getUniqueHash(): string;
}
