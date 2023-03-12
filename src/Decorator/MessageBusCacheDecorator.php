<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Decorator;

use PBaszak\MessengerCacheBundle\Attribute\Invalidate;
use PBaszak\MessengerCacheBundle\Contract\Optional\DynamicTags;
use PBaszak\MessengerCacheBundle\Contract\Optional\OwnerIdentifier;
use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheKeyProviderInterface;
use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheManagerInterface;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Contract\Required\CacheInvalidation;
use PBaszak\MessengerCacheBundle\Message\InvalidateAsync;
use PBaszak\MessengerCacheBundle\Stamps\InvalidationResultsStamp;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;

#[AsDecorator('messenger.bus.default')]
class MessageBusCacheDecorator implements MessageBusInterface
{
    public function __construct(
        private MessageBusInterface $decorated,
        private MessengerCacheKeyProviderInterface $cacheKeyProvider,
        private MessengerCacheManagerInterface $cacheManager,
    ) {
    }

    /**
     * @param StampInterface[] $stamps
     */
    public function dispatch(object $message, array $stamps = []): Envelope
    {
        if ($message instanceof Cacheable) {
            return $this->dispatchCacheableMessage($message, $stamps);
        } elseif ($message instanceof CacheInvalidation) {
            return $this->dispatchCacheInvalidationMessage($message, $stamps);
        }

        return $this->decorated->dispatch($message, $stamps);
    }

    /**
     * @param StampInterface[] $stamps
     */
    private function dispatchCacheableMessage(Cacheable $message, array $stamps): Envelope
    {
        $this->cacheManager->setMessageBus($this->decorated);

        return $this->cacheManager->get(
            $message,
            $stamps,
            $this->cacheKeyProvider->createKey($message, $stamps),
            function () use ($message, $stamps): Envelope {
                return $this->decorated->dispatch($message, $stamps);
            }
        );
    }

    /**
     * @param StampInterface[] $stamps
     */
    private function dispatchCacheInvalidationMessage(CacheInvalidation $message, array $stamps): Envelope
    {
        $invalidates = (new \ReflectionClass($message))->getAttributes(Invalidate::class);
        if (empty($invalidates)) {
            throw new \LogicException('CacheInvalidation message must have at least one Invalidate attribute.');
        }
        $invalidates = array_map(fn ($invalidate): Invalidate => $invalidate->newInstance(), $invalidates);

        $invalidationResults = [];
        foreach ($invalidates as $invalidate) {
            /** @var OwnerIdentifier $message */
            if ($invalidate->useOwnerIdentifier && !$message instanceof OwnerIdentifier) {
                throw new \LogicException('CacheInvalidation message must implement OwnerIdentifier interface when useOwnerIdentifier is set to true.');
            }

            if ($invalidate->invalidateBeforeDispatch) {
                if ($invalidate->invalidateAsync) {
                    $this->dispatch(
                        new InvalidateAsync(
                            $message instanceof DynamicTags ? $message->getDynamicTags() : $invalidate->tags,
                            $invalidate->groups ?? [],
                            $invalidate->useOwnerIdentifier ? $message->getOwnerIdentifier() : null,
                            $invalidate->useOwnerIdentifierForTags,
                            $invalidate->adapter,
                        )
                    );
                } else {
                    $invalidationResults[] = $this->cacheManager->invalidate(
                        $message instanceof DynamicTags ? $message->getDynamicTags() : $invalidate->tags,
                        $invalidate->groups ?? [],
                        $invalidate->useOwnerIdentifier ? $message->getOwnerIdentifier() : null,
                        $invalidate->useOwnerIdentifierForTags,
                        $invalidate->adapter,
                    );
                }
            }
        }

        try {
            /** @var Envelope */
            $envelope = $this->decorated->dispatch($message, $stamps);
        } catch (\Throwable $exception) {
            throw $exception;
        } finally {
            foreach ($invalidates as $invalidate) {
                if (!$invalidate->invalidateBeforeDispatch) {
                    if ($invalidate->invalidateAsync) {
                        $this->dispatch(
                            new InvalidateAsync(
                                $message instanceof DynamicTags ? $message->getDynamicTags() : $invalidate->tags,
                                $invalidate->groups ?? [],
                                $invalidate->useOwnerIdentifier ? $message->getOwnerIdentifier() : null,
                                $invalidate->useOwnerIdentifierForTags,
                                $invalidate->adapter,
                            )
                        );
                    } else {
                        if ((isset($exception) && $invalidate->invalidateOnException) || !isset($exception)) {
                            $invalidationResults[] = $this->cacheManager->invalidate(
                                $message instanceof DynamicTags ? $message->getDynamicTags() : $invalidate->tags,
                                $invalidate->groups ?? [],
                                $invalidate->useOwnerIdentifier ? $message->getOwnerIdentifier() : null,
                                $invalidate->useOwnerIdentifierForTags,
                                $invalidate->adapter,
                            );
                        }
                    }
                }
            }

            if (isset($envelope)) {
                foreach ($invalidationResults as $result) {
                    $envelope = $envelope->with(new InvalidationResultsStamp($result));
                }
            }
        }

        return $envelope;
    }
}
