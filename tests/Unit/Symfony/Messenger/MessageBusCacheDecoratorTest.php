<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Unit\Symfony\Messenger;

use PBaszak\MessengerCacheBundle\Decorator\MessageBusCacheDecorator;
use PBaszak\MessengerCacheBundle\Tests\Helper\Domain\Decorator\LoggingMessageBusDecorator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

/** @group unit */
class MessageBusCacheDecoratorTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('cachedMessage.bus');
    }

    /** @test */
    public function shouldInstanceOfDecorator(): void
    {
        $this->assertInstanceOf(LoggingMessageBusDecorator::class, $this->messageBus);
        $this->assertInstanceOf(MessageBusCacheDecorator::class, $this->messageBus->decorated);
    }
}
