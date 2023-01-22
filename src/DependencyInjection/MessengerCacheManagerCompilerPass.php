<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\DependencyInjection;

use PBaszak\MessengerCacheBundle\Contract\MessengerCacheManagerInterface;
use PBaszak\MessengerCacheBundle\MessengerCacheBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MessengerCacheManagerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(MessengerCacheManagerInterface::class)
            ->addArgument('$kernelCacheDir', '%kernel.cache_dir%')
            ->addTag(MessengerCacheBundle::DI_ALIAS.'.messenger_cache_manager');
    }
}
