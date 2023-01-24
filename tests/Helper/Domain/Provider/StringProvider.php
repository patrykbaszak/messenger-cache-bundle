<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Domain\Provider;

use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Provider\StringProviderInterface;

class StringProvider implements StringProviderInterface
{
    public function generateRandomString(int $length): string
    {
        $repeatXTimes = (int) ceil($length / 61); // 61 follows from the character table below

        return substr(
            str_shuffle(
                str_repeat(
                    $x = str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', $repeatXTimes),
                    (int) ceil($length / strlen($x))
                )
            ),
            1,
            $length
        );
    }
}
