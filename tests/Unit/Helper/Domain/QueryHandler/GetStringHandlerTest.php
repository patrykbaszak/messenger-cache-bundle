<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Unit\Helper\Domain\QueryHandler;

use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetString;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/** @group unit */
class GetStringHandlerTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get(MessageBusInterface::class);
    }

    /** @test */
    public function shouldReturnOrderedString(): void
    {
        $length = rand(1, 100);
        $output = $this->handle(
            new GetString($length)
        );

        $this->assertIsString($output);
        $this->assertEquals($length, strlen($output));
    }
}
