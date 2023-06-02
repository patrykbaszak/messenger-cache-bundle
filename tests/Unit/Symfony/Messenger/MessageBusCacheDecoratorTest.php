<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Unit\Symfony\Messenger;

use PBaszak\MessengerCacheBundle\Decorator\MessageBusCacheDecorator;
use PBaszak\MessengerCacheBundle\Decorator\MessageBusCacheEventsDecorator;
use PBaszak\MessengerCacheBundle\Tests\Helper\Domain\Decorator\LoggingMessageBusDecorator;
use ReflectionObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

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
        $this->assertInstanceOf(MessageBusCacheEventsDecorator::class, $this->messageBus->decorated);
        $decorator = (new ReflectionObject($this->messageBus->decorated))->getProperty('decorated');
        $decorator->setAccessible(true);
        $decorator = $decorator->getValue($this->messageBus->decorated);
        $this->assertInstanceOf(MessageBusCacheDecorator::class, $decorator);
        $messageBus = (new ReflectionObject($decorator))->getProperty('decorated');
        $messageBus->setAccessible(true);
        $messageBus = $messageBus->getValue($decorator);
        $this->assertInstanceOf(MessageBusInterface::class, $messageBus);
    }
}
