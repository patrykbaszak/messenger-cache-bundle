<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Unit\Helper\Domain\Provider;

use PBaszak\MessengerCacheBundle\Tests\Helper\Domain\Provider\StringProvider;
use PHPUnit\Framework\TestCase;

/** @group unit */
class StringProviderTest extends TestCase
{
    /** @test */
    public function shouldReturnOrderedLengthString(): void
    {
        $stringProvider = new StringProvider();

        for ($i = 1; $i <= 10; $i++) {
            $length = rand(1, 100);
            $string = $stringProvider->generateRandomString($length);

            $this->assertEquals($length, strlen($string));
        }
    }
}
