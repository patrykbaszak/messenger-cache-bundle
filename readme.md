# Messenger Cache Bundle #

## Installation ##

```sh
composer require pbaszak/messenger-cache-bundle
```
W pliku `config/bundles.php`
```php
<?php

return [
    // ...
    PBaszak\MessengerCacheBundle\MessengerCacheBundle::class => ['all' => true],
];
```

<hr>
<hr>

## Configuration ##

Create or copy file `messenger_cache.yaml`:
```sh
# copy
cp vendor/pbaszak/messenger-cache-bundle/config/packages/messenger_cache.yaml config/packages/messenger_cache.yaml
#
# or
#
# create
touch config/packages/messenger_cache.yaml
```
Recommended initial settings:
```yaml
# config/packages/messenger_cache.yaml
messenger_cache:
    # If You use RefreshAsync (it's message) / refreshAfter (in Cache attribute argument) then
    # you must have declared time of live info that refresh was triggered. It's deleted
    # after succesful refresh. To short value may trigger more than one refresh action for 
    # specific item. Recommended is 10 minutes.
    refresh_triggered_ttl: 600
    # aliases for pools to use them in cache attribute and cache invalidation attribute
    # aliases are required and `default` alias is required.
    pools:
        default: filesystem
        runtime: runtime
        redis: redis
    # this is default value and You don't need to add it, but if You want decorates different buses
    # you can declare them all here.
    decorated_message_buses:
        - cachedMessage.bus
    # message bus decorators whic You want add. The main cache decorator has 
    # priority 0 if higher it will be closer to main message bus.
    message_bus_decorators:
        '-1': PBaszak\MessengerCacheBundle\Tests\Helper\Domain\Decorator\LoggingMessageBusDecorator
```
Configuration description:
| Parameter | Description |
|----------|-------------|
| `messenger_cache.pools`  | List of pools supported by `MessengerCacheBundle`, only `default` is mandatory and will be selected if no other pool is specified in the `Cache` attribute. Using aliases is mandatory. Adapters must be services, so you may need to define them in the `services.yaml` file. Example below the table. |

Example declaration of pools as services:
```yaml
# config/cache.yaml
framework:
    cache:
        default_redis_provider: 'redis://redis:6379'
        pools:
            runtime: 
                adapter: cache.adapter.array
            filesystem: 
                adapter: cache.adapter.filesystem
            redis:
                adapter: cache.adapter.redis_tag_aware
                tags: true

```
**NOTE: Your code may (and probably will) look a bit different.**

There are two more commands:
 - `PBaszak\MessengerCacheBundle\Message\InvalidateAsync`,
 - `PBaszak\MessengerCacheBundle\Message\RefreshAsync`,

You need to add them to asynchronous processing yourself. If you don't do this, they will be executed synchronously, which will affect the performance of your application when retrieving data from cache.

<hr>
<hr>

## Usage ##

### **Example no 1 (Cache)** ###

An example class handled by Symfony Messenger as a Message, which has its own Handler that always returns a random string.

```php
# src/Application/Query/GetRandomString.php
use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;

#[Cache()]
class GetRandomString implements Cacheable
{
}
```

Any Manager that invokes the Message we are interested in and returns its response:

```php
# src/Domain/Manager/StringsManager.php
use App\Application\Query\GetRandomString;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\HandleTrait;

class StringsManager
{
    use HandleTrait;

    public function __construct(MessageBusInterface $cachedMessageBus)
    {
        $this->messageBus = $cachedMessageBus;
    }

    public function getAlwaysSameRandomString(): string
    {
        return $this->handle(
            new GetRandomString()
        );
    }
}

$stringsManager = new StringsManager();
$result0 = $stringsManager->getAlwaysSameRandomString();
$result1 = $stringsManager->getAlwaysSameRandomString();

var_dump($result0 === $result1); // true
```

### **Example no 2 (CacheInvalidation)** ###

```php
# src/Application/Query/GetRandomString.php
use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;

#[Cache(tags: ['string_tag'])]
class GetRandomString implements Cacheable
{
}
```
```php
# src/Application/Command/DoSth.php
use PBaszak\MessengerCacheBundle\Attribute\CacheInvalidation;
use PBaszak\MessengerCacheBundle\Contract\Required\CacheInvalidation;

#[Invalidate(['string_tag'])]
class DoSth implements CacheInvalidation
{
}
```
```php
# src/Domain/Manager/StringsManager.php
use App\Application\Command\DoSth;
use App\Application\Query\GetRandomString;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\HandleTrait;

class StringsManager
{
    use HandleTrait;

    public function __construct(MessageBusInterface $cachedMessageBus)
    {
        $this->messageBus = $cachedMessageBus;
    }

    public function getAlwaysSameRandomString(): string
    {
        return $this->handle(
            new GetRandomString()
        );
    }

    public function doSth(): void
    {
        $this->handle(
            new DoSth()
        );
    }
}

$stringsManager = new StringsManager();
$result0 = $stringsManager->getAlwaysSameRandomString();
$stringsManager->doSth();
$result1 = $stringsManager->getAlwaysSameRandomString();

var_dump($result0 === $result1); // false
```

### **Example no 3 (ForceCacheRefreshStamp)** ###

```php
# src/Infrastructure/Symfony/Messenger/MessageBusDecorator.php
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;
use PBaszak\MessengerCacheBundle\Stamps\ForceCacheRefreshStamp;

#[AsDecorator('cachedMessage.bus')]
class MessageBusDecorator implements MessageBusInterface
{
    private bool $forceCacheRefresh;

    public function __construct(
        private MessageBusInterface $decorated,
        RequestStack $requestStack,
    ) {
        $this->forceCacheRefresh = $requestStack->getMainRequest()->query->has('forceCacheRefresh');
    }

    /** @param StampInterface[] $stamps */
    public function dispatch(object $message, array $stamps = []): Envelope
    {
        if ($this->forceCacheRefresh) {
            $stamps = array_merge($stamps, [new ForceCacheRefreshStamp()]);
        }

        return $this->decorated->dispatch($message, $stamps);
    }
}
```

```sh
curl --location --request GET 'http://localhost/strings/random?forceCacheRefresh'
```

```php
# src/Domain/Manager/StringsManager.php
use App\Application\Query\GetRandomString;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\HandleTrait;

class StringsManager
{
    use HandleTrait;

    public function __construct(MessageBusInterface $cachedMessageBus)
    {
        $this->messageBus = $cachedMessageBus;
    }

    public function getAlwaysSameRandomString(): string
    {
        return $this->handle(
            new GetRandomString()
        );
    }
}

$stringsManager = new StringsManager();
$result0 = $stringsManager->getAlwaysSameRandomString();
$result1 = $stringsManager->getAlwaysSameRandomString();

var_dump($result0 === $result1); // false
```

<hr>
<hr>

## Detailed settings ##

### **Attributes** ###

### Cache ###

| Parameter | Description |
|:---------|:-----|
| `$ttl` | The cache lifetime in seconds. You may also be interested in the `DynamicTtl` interface, which allows you to dynamically choose the `ttl` for the cache. |
| `$refreshAfter` | The cache validity period in seconds. After this time has elapsed, when the `Bundle` is called again, it will try to refresh this cache. **NOTE: You need to add `PBaszak\MessengerCacheBundle\Message\RefreshAsync` to your `MessageBusInterface`-based queue system (AMQP, Redis, Doctrine - see packages: `Symfony/amqp-messenger`, `Symfony/redis-messenger`, `Symfony/doctrine-messenger`)**. |
| `$pool` | The pool alias that will be used to handle the cache. |
| `$tags` | A list of constant tags for a given resource, but you may be interested in the `DynamicTags` interface, which allows you to fully customize it. |

### Invalidate ###

| Parameter | Description |
|:---------|:-----|
| `$tags` | A list of constant tags that should be invalidated. You can replace them with the `DynamicTags` interface |
| `$pool` | An pool to invalidate. If `null`, all `TagAwareAdapterInterface` pools will be invalidated. |
| `$invalidateBeforeDispatch` | If you need to, cache invalidation can be performed before executing the actual `Message`. |
| `$invalidateOnException` | If handling the `Message` results in an exception, cache invalidation can still be performed after executing the `Message` if you want. |
| `$invalidateAsync` | If `true` and `PBaszak\MessengerCacheBundle\Message\InvalidateAsync` is present in your queue system (`Symfony/amqp-messenger`, `Symfony/redis-messenger`, `Symfony/doctrine-messenger`) as an asynchronously executed message, cache invalidation will be performed asynchronously. |

### **Optional Interfaces** ###

| Interface | Description |
|:----------|:-----|
| `CacheableCallback` | The `isCacheable(): bool` method allows you to dynamically decide whether or not to use the cache. |
| `DynamicTags` | The `getDynamicTags(): array` method allows you to dynamically provide tags for cache handling (replaces the `Cache(tags: [])` property). |
| `DynamicTtl` | The `getDynamicTtl(): int` method allows you to dynamically provide a `ttl` value in seconds for the cache. |
| `HashableInstance` | The `getHashableInstance(): Cacheable` method should return the `Message` object in a form that can be hashed. You may need to use this interface if the cache you want to store should be available to multiple users and at the same time you have user context in the `Message`. This method allows you to get rid of it. |
| `UniqueHash` | The `getUniqueHash(): string` method allows you to define the hash for the `Message` instance yourself. You can use this instead of `HashableInstance`. |
