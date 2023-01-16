<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Unit\Helper\Domain\CommandHandler;

use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Command\DoNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/** @group unit */
class DoNothingHandlerTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->messageBus = self::getContainer()->get(MessageBusInterface::class);
    }

    /** @test */
    public function shouldReturnNothing(): void
    {
        $output = $this->handle(
            new DoNothing()
        );

        $this->assertFalse(isset($output));
    }
}
