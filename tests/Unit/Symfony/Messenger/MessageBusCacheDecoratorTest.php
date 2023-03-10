<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Unit\Symfony\Messenger;

use PBaszak\MessengerCacheBundle\Decorator\MessageBusCacheDecorator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

/** @group unit */
class MessageBusCacheDecoratorTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
    }

    /** @test */
    public function shouldInstanceOfDecorator(): void
    {
        $this->assertInstanceOf(MessageBusCacheDecorator::class, $this->messageBus);
    }
}
