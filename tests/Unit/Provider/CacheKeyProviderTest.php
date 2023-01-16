<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Unit\Provider;

use PBaszak\MessengerCacheBundle\Provider\CacheKeyProvider;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetString;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Stamp\HandlerArgumentsStamp;

/** @group unit */
class CacheKeyProviderTest extends TestCase
{
    /** @test */
    public function shouldAlwaysReturnSameCacheKey(): void
    {
        $provider = new CacheKeyProvider();
        $length = rand(1, 100);

        $message = new GetString($length);

        $this->assertEquals($provider->createKey($message), $provider->createKey($message));
    }

    /** @test */
    public function shouldAlwaysIgnoreStamps(): void
    {
        $provider = new CacheKeyProvider();
        $length = rand(1, 100);

        $message = new GetString($length);

        $this->assertEquals($provider->createKey($message), $provider->createKey($message, [new HandlerArgumentsStamp([])]));
    }
}
