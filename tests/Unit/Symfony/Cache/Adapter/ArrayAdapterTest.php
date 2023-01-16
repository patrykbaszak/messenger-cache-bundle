<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Unit\Symfony\Cache\Adapter;

use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetString;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Cache\ItemInterface;

/** @group unit */
class ArrayAdapterTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get(MessageBusInterface::class);
    }

    /** @test */
    public function shouldStoreOutputInCache(): void
    {
        $length[0] = rand(1, 25);
        $length[1] = rand(26, 50);

        $v0[0] = $this->getString($length[0]);
        $v1[0] = $this->getString($length[1]);
        
        $v0[1] = $this->getString($length[0]);
        $v1[1] = $this->getString($length[1]);

        $this->assertNotEquals($v0[0], $v1[0]);
        $this->assertEquals($v0[0], $v0[1]);
        $this->assertEquals($v1[0], $v1[1]);
    }

    private function getString(int $length): string
    {
        static $adapter = new ArrayAdapter();
        return $adapter->get('test_length_' . (string) $length, function (ItemInterface $item) use ($length) {
            return $this->handle(
                new GetString($length)
            );
        });
    }
}
