<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Unit\Provider;

use PBaszak\MessengerCacheBundle\Contract\Optional\DynamicKeyPart;
use PBaszak\MessengerCacheBundle\Contract\Optional\UniqueHash;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Provider\CacheKeyProvider;
use PBaszak\MessengerCacheBundle\Stamps\ForceCacheRefreshStamp;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetString;
use PHPUnit\Framework\TestCase;

/** @group unit */
class CacheKeyProviderTest extends TestCase
{
    /** @test */
    public function shouldAlwaysReturnSameCacheKey(): void
    {
        $provider = new CacheKeyProvider();
        $length = rand(1, 100);

        $message = new class($length) extends GetString implements Cacheable, UniqueHash {
            public function getUniqueHash(): string
            {
                return (string) $this->length;
            }
        };

        $this->assertEquals($provider->createKey($message), $provider->createKey($message));
    }

    /** @test */
    public function shouldAlwaysIgnoreStamps(): void
    {
        $provider = new CacheKeyProvider();
        $length = rand(1, 100);

        $message = new class($length) extends GetString implements Cacheable, UniqueHash {
            public function getUniqueHash(): string
            {
                return (string) $this->length;
            }
        };

        $this->assertEquals($provider->createKey($message), $provider->createKey($message, [new ForceCacheRefreshStamp()]));
    }

    /** @test */
    public function shouldReturnKeyWithDynamicKeyPart(): void
    {
        $provider = new CacheKeyProvider();
        $length = rand(1, 100);
        $dynamicKeyPart = uniqid();

        $message = new class($length, $dynamicKeyPart) extends GetString implements Cacheable, UniqueHash, DynamicKeyPart {
            public function __construct(int $length, private string $dynamicKeyPart)
            {
                parent::__construct($length);
            }

            public function getUniqueHash(): string
            {
                return (string) $this->length;
            }

            public function getDynamicKeyPart(): string
            {
                return $this->dynamicKeyPart;
            }
        };

        $this->assertTrue(str_contains($provider->createKey($message), $dynamicKeyPart));
    }
}
