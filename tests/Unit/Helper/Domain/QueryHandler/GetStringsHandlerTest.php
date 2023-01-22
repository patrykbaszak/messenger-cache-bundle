<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Unit\Helper\Domain\QueryHandler;

use PBaszak\MessengerCacheBundle\Tests\Helper\Application\DTO\Strings;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetArrayOfStrings;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetObjectOfStrings;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetStrings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/** @group unit */
class GetStringsHandlerTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get(MessageBusInterface::class);
    }

    /** @test */
    public function shouldReturnStringsDTO(): void
    {
        $length = rand(1, 100);
        $numberOfStrings = rand(1, 10);

        $output = $this->handle(
            new GetStrings($length, $numberOfStrings)
        );

        $this->assertInstanceOf(Strings::class, $output);
        $this->assertCount($numberOfStrings, $output->strings);

        foreach ($output->strings as $string) {
            $this->assertEquals($length, strlen($string));
        }
    }

    /** @test */
    public function shouldReturnArrayOfStrings(): void
    {
        $length = rand(1, 100);
        $numberOfStrings = rand(1, 10);

        $output = $this->handle(
            new GetArrayOfStrings($length, $numberOfStrings)
        );

        $this->assertIsArray($output);
        $this->assertCount($numberOfStrings, $output);

        foreach ($output as $string) {
            $this->assertEquals($length, strlen($string));
        }
    }

    /** @test */
    public function shouldReturnObjectOfStrings(): void
    {
        $length = rand(1, 100);
        $numberOfStrings = rand(1, 10);

        $output = $this->handle(
            new GetObjectOfStrings($length, $numberOfStrings)
        );

        $this->assertIsObject($output);

        foreach ($output as $string) {
            $i ??= 0;
            ++$i;
            $this->assertEquals($length, strlen($string));
        }

        $this->assertEquals($numberOfStrings, $i);
    }
}
