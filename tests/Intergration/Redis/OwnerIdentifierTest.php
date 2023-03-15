<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Integration\Redis;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Attribute\Invalidate;
use PBaszak\MessengerCacheBundle\Contract\Optional\OwnerIdentifier;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Contract\Required\CacheInvalidation;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Command\DoNothing;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetArrayOfStrings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

#[Invalidate(useOwnerIdentifier: true)]
class DoInvalidation extends DoNothing implements CacheInvalidation, OwnerIdentifier
{
    public function getOwnerIdentifier(): string
    {
        return 'company_1634566';
    }
}

#[Cache(pool: 'redis')]
class GetCachedArrayOfStrings extends GetArrayOfStrings implements Cacheable, OwnerIdentifier
{
    public function getOwnerIdentifier(): string
    {
        return 'company_1634566';
    }
}

/** @group integration */
class OwnerIdentifierTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
        self::getContainer()->get('messenger_cache.manager')->clear(pool: 'redis');
    }

    /** @test */
    public function shouldReturnSameResponseGetCachedArrayOfStrings(): void
    {
        $result1 = $this->handle(new GetCachedArrayOfStrings());
        $result2 = $this->handle(new GetCachedArrayOfStrings());

        $this->assertEquals($result1, $result2);
    }

    /** @test */
    public function shouldReturnNotSameResponseGetCachedArrayOfStrings(): void
    {
        $result1 = $this->handle(new GetCachedArrayOfStrings());
        $this->handle(new DoInvalidation());
        $result2 = $this->handle(new GetCachedArrayOfStrings());

        $this->assertNotEquals($result1, $result2);
    }
}
