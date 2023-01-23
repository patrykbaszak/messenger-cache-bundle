<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle.
 *
 * @author Patryk Baszak <patryk.baszak@gmail.com>
 */
class MessengerCacheBundle extends Bundle
{
    public const ALIAS = 'messenger_cache';

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DependencyInjection\MessengerCacheManagerPass());
    }
}
