<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Unit\Helper\Domain\QueryHandler;

use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetNull;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/** @group unit */
class GetNullHandlerTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get(MessageBusInterface::class);
    }

    /** @test */
    public function shouldReturnNull(): void
    {
        $output = $this->handle(
            new GetNull()
        );

        $this->assertNull($output);
    }
}
