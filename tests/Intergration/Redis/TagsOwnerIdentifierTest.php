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
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetObjectOfStrings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

#[Invalidate(useOwnerIdentifierForTags: true, tags: ['tags'])]
class DoTagsOwnerInvalidation extends DoNothing implements CacheInvalidation, OwnerIdentifier
{
    public function getOwnerIdentifier(): string
    {
        return 'company_1634566';
    }
}

#[Invalidate(useOwnerIdentifier: true)]
class DoTaggedOwnerInvalidation extends DoNothing implements CacheInvalidation, OwnerIdentifier
{
    public function getOwnerIdentifier(): string
    {
        return 'company_1634566';
    }
}

#[Cache(pool: 'redis', tags: ['tags'])]
class GetTagsOwnerCachedArrayOfStrings extends GetArrayOfStrings implements Cacheable, OwnerIdentifier
{
    public function getOwnerIdentifier(): string
    {
        return 'company_1634566';
    }
}

#[Cache(pool: 'redis', tags: ['some_other_tags'], useOwnerIdentifierForTags: true)]
class GetTagsOwnerCachedObjectOfStrings extends GetObjectOfStrings implements Cacheable, OwnerIdentifier
{
    public function getOwnerIdentifier(): string
    {
        return 'company_1634566';
    }
}

/** @group integration */
class TagsOwnerIdentifierTest extends KernelTestCase
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
        $result1 = $this->handle(new GetTagsOwnerCachedArrayOfStrings());
        $result2 = $this->handle(new GetTagsOwnerCachedArrayOfStrings());

        $this->assertEquals($result1, $result2);
    }

    /** @test */
    public function shouldReturnNotSameResponseGetCachedArrayOfStrings(): void
    {
        $result1 = $this->handle(new GetTagsOwnerCachedArrayOfStrings());
        $this->handle(new DoTagsOwnerInvalidation());
        $result2 = $this->handle(new GetTagsOwnerCachedArrayOfStrings());

        $this->assertNotEquals($result1, $result2);
    }

    /** @test */
    public function shouldReturnNotSameResponseGetCachedArrayOfStringsWhenInvalidateByOwner(): void
    {
        $result1 = $this->handle(new GetTagsOwnerCachedArrayOfStrings());
        $this->handle(new DoTaggedOwnerInvalidation());
        $result2 = $this->handle(new GetTagsOwnerCachedArrayOfStrings());

        $this->assertNotEquals($result1, $result2);
    }

    /** @test */
    public function shouldReturnSameResponseGetTagsOwnerCachedObjectOfStrings(): void
    {
        $result1 = $this->handle(new GetTagsOwnerCachedObjectOfStrings());
        $result2 = $this->handle(new GetTagsOwnerCachedObjectOfStrings());

        $this->assertEquals($result1, $result2);
    }

    /** @test */
    public function shouldReturnSameResponseGetTagsOwnerCachedObjectOfStringsWithWrongInvalidation(): void
    {
        $result1 = $this->handle(new GetTagsOwnerCachedObjectOfStrings());
        $this->handle(new DoTagsOwnerInvalidation());
        $result2 = $this->handle(new GetTagsOwnerCachedObjectOfStrings());

        $this->assertEquals($result1, $result2);
    }

    /** @test */
    public function shouldReturnNotSameResponseGetTagsOwnerCachedObjectOfStringsWhenInvalidateByOwner(): void
    {
        $result1 = $this->handle(new GetTagsOwnerCachedObjectOfStrings());
        $this->handle(new DoTaggedOwnerInvalidation());
        $result2 = $this->handle(new GetTagsOwnerCachedObjectOfStrings());

        $this->assertNotEquals($result1, $result2);
    }
}
