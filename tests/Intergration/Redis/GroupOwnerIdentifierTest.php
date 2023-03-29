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

#[Invalidate(useOwnerIdentifier: true, groups: ['group'])]
class DoGroupOwnerInvalidation extends DoNothing implements CacheInvalidation, OwnerIdentifier
{
    public function getOwnerIdentifier(): string
    {
        return 'company_1634566';
    }
}

#[Invalidate(useOwnerIdentifier: true)]
class DoOwnerInvalidation extends DoNothing implements CacheInvalidation, OwnerIdentifier
{
    public function getOwnerIdentifier(): string
    {
        return 'company_1634566';
    }
}

#[Cache(pool: 'redis', group: 'group')]
class GetGroupOwnerCachedArrayOfStrings extends GetArrayOfStrings implements Cacheable, OwnerIdentifier
{
    public function getOwnerIdentifier(): string
    {
        return 'company_1634566';
    }
}

/** @group integration */
class GroupOwnerIdentifierTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('cachedMessage.bus');
        self::getContainer()->get('messenger_cache.manager')->clear(pool: 'redis');
    }

    /** @test */
    public function shouldReturnSameResponseGetCachedArrayOfStrings(): void
    {
        $result1 = $this->handle(new GetGroupOwnerCachedArrayOfStrings());
        $result2 = $this->handle(new GetGroupOwnerCachedArrayOfStrings());

        $this->assertEquals($result1, $result2);
    }

    /** @test */
    public function shouldReturnNotSameResponseGetCachedArrayOfStrings(): void
    {
        $result1 = $this->handle(new GetGroupOwnerCachedArrayOfStrings());
        $this->handle(new DoGroupOwnerInvalidation());
        $result2 = $this->handle(new GetGroupOwnerCachedArrayOfStrings());

        $this->assertNotEquals($result1, $result2);
    }

    /** @test */
    public function shouldReturnNotSameResponseGetCachedArrayOfStringsWhenInvalidateByOwner(): void
    {
        $result1 = $this->handle(new GetGroupOwnerCachedArrayOfStrings());
        $this->handle(new DoOwnerInvalidation());
        $result2 = $this->handle(new GetGroupOwnerCachedArrayOfStrings());

        $this->assertNotEquals($result1, $result2);
    }
}
