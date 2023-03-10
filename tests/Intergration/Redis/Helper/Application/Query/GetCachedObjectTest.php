<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Integration\Redis\Helper\Application\Query;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

#[Cache(adapter: 'redis')]
class GetCachedObject extends GetObject implements Cacheable
{
}

/** @group integration */
class GetCachedObjectTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
    }
}
