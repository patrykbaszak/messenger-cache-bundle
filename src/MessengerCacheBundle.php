<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Bundle.
 * 
 * @author Patryk Baszak <patryk.baszak@gmail.com>
 */
class MessengerCacheBundle extends AbstractBundle
{
    public const DI_ALIAS = 'pbaszak_messenger_cache';

    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}