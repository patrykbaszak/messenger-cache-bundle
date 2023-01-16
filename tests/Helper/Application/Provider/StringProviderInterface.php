<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Application\Provider;

interface StringProviderInterface
{
    public function generateRandomString(int $length): string;
}
