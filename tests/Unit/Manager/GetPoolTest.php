<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Unit\Manager;

use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;

/** @group unit */
class GetPoolTest extends KernelTestCase
{
    private MessengerCacheManagerInterface $manager;

    protected function setUp(): void
    {
        $this->manager = self::getContainer()->get('messenger_cache.manager');
    }

    /**
     * @test
     *
     * @see config/packages/messenger_cache.yaml The redis pool is mapped to redis adapter.
     * @see config/packages/cache.yaml The redis adapter is configured as cache.adapter.redis_tag_aware.
     */
    public function shouldReturnRedisAdapter(): void
    {
        $this->assertInstanceOf(RedisTagAwareAdapter::class, $this->manager->getPool('redis'));
    }

    /** @test */
    public function shouldThrowLogicExceptionIfPoolDoesNotExist(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Pool "non_existing_pool" is not configured.');

        $this->manager->getPool('non_existing_pool');
    }
}
