# Messenger Cache Bundle #

## Compatibility ##
- **PHP 8.0** - **PHP 8.2**
- **Symfony 5.4** - **Symfony 6.2**

## Installation ##

```sh
composer require pbaszak/symfony-messenger-cache-bundle
```
W pliku `config/bundles.php`
```php
<?php

return [
    // ...
    PBaszak\MessengerCacheBundle\MessengerCacheBundle::class => ['all' => true],
];
```

## Quick start ##
### **Step 0** ###
After installing the package, first make sure that you have defined `default_bus` in the `config/packages/messenger.yaml` file. If you don't have it, Symfony will either return an error or not, which is also a problem if not all `MessageBusInterface $messageBus` injections in your application are to be decorated by `MessageBusCacheDecorator`.
In most cases, it should look like this:
```yaml
framework:
    messenger:
        default_bus: messenger.bus.default
        buses:
            messenger.bus.default:
```
Note that there is no declaration of `cachedMessage.bus` here, it has already been declared by this bundle and you can use it by changing the constructor argument name from `MessageBusInterface $messageBus` to `MessageBusInterface $cachedMessageBus`.
### **Step 1** ###
During the first compilation of Symfony, an error may occur stating that you have not defined a default `cache pool`, which you can define in the `messenger_cache.pools` or `framework.cache.pools` array. This array is responsible for the list of cache adapters that the `MessengerCacheManager` will handle. To declare it correctly, start by visiting the `config/packages/cache.yaml` file, where you will find the definitions of `cache pools`. The default `pool` is named `app` in the case of definitions in `framework.cache.pools` or `default` in the case of alias definitions in `messenger_cache.pools`. Below is an example file from `config/packages/cache.yaml`:
```yaml
framework:
    cache:
        pools:
            app: # By default, the pool used by the bundle is the one named `app`.
                adapter: cache.adapter.redis_tag_aware
                tags: true
            runtime: 
                adapter: cache.adapter.array
                tags: true
            filesystem:
                adapter: cache.adapter.filesystem
```
There is no obligation to use tag supporting adapters if you will not use cache invalidation. However, even then I recommend using adapters that support caching.
You do not have the file `config/packages/messenger_cache.yaml` in your project and you do not need it as part of a "quick start". But below in this readme file you will find information on what such a file should look like and what configuration options it has.

### Step 2 ###
Modify your Message class, the response of which you want to cache, according to the example below. Note that I have chosen a more complex example to show you how to associate cache with a user in such a way that it will be possible to invalidate this cache, which I think will be the most common use case:

```php
# src/Application/User/Query/GetUserConfig.php
use PBaszak\MessengerCacheBundle\Attribute\Cache; # required attribute
use PBaszak\MessengerCacheBundle\Contract\Optional\DynamicTags; # optional interface
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable; # required interface

#[Cache(ttl: 3600)]
class GetUserConfig implements Cacheable, DynamicTags
{
    public function __construct(public readonly string $userId) {}

    public function getDynamicTags(): array
    {
        return ['user_' . $this->userId];
    }
}
```

### Step 3 ###
Modify the constructor of the class where you execute `$this->messageBus->dispatch(new GetUserConfig($userId))` or `$this->handle(new GetUserConfig($userId))`.

Before modification:
```php
class UserConfigController extends AbstractController
{
    public function __construct(MessageBusInterface $messageBus) {}
}
```

After modification:
```php
class UserConfigController extends AbstractController
{
    public function __construct(MessageBusInterface $cachedMessageBus) {}
}
```
**DONE**.<br> Now, if you call `GetUserConfig()` in the `UserConfigController` class, the response will be cached in the default cache pool. 

### **Extra Step** (invalidation) ###
```php
# src/Application/User/Command/UpdateUserConfig.php
use PBaszak\MessengerCacheBundle\Attribute\Invalidate; # required attribute
use PBaszak\MessengerCacheBundle\Contract\Optional\DynamicTags; # optional interface
use PBaszak\MessengerCacheBundle\Contract\Required\CacheInvalidation; # required interface

#[Invalidate()]
class UpdatetUserConfig implements CacheInvalidation, DynamicTags
{
    public function __construct(
        public readonly string $usesId,
        public readonly array $config,
    ) {}

    public function getDynamicTags(): array
    {
        return ['user_' . $this->userId];
    }
}
```
As you can see, the `getDynamicTags` method has not changed, which is why I strongly recommend placing this method in Traits.

### **Extra step** (removing user context, e.g. in case of cache for user group)
```php
# src/Application/User/Query/GetUserConfig.php
use PBaszak\MessengerCacheBundle\Attribute\Cache; # required attribute
use PBaszak\MessengerCacheBundle\Contract\Optional\HashableInstance; # optional interface
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable; # required interface

#[Cache(refreshAfter: 3600)] # The "refreshAfter" will cause that after an hour, on the next cache call, it will be asynchronously refreshed and the old cache will be returned.
class GetCompanyConfig implements Cacheable, HashableInstance
{
    public function __construct(
        public readonly ?User $user,
        public readonly string $companyId,
    ) {}

    public function getHashableInstance(): Cacheable
    {
        return new self(null, $this->companyId); # The original Message will still be processed, but it will be used to create a unique hash. Therefore, if we didn't remove the user context, the cache would only be available to them, not the entire company.
    }
}
```
What if I can't delete user context, for example because I don't want to write new self() with 20 constructor arguments? I have an alternative solution for you!

```php
# src/Application/User/Query/GetUserConfig.php
use PBaszak\MessengerCacheBundle\Attribute\Cache; # required attribute
use PBaszak\MessengerCacheBundle\Contract\Optional\UniqueHash; # optional interface
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable; # required interface

#[Cache(refreshAfter: 3600)]
class GetCompanyConfig implements Cacheable, UniqueHash
{
    public function __construct(
        public readonly ?User $user,
        public readonly string $companyId,
    ) {}

    public function getUniqueHash(): string
    {
        return 'company_' . $this->companyId;
    }
}
```
Will that be enough? Won't the cache interfere with the cache of another Message that has the same UniqueHash?<br>
**No, it won't.** The cache key also includes a hash generated from the full class name of the Message.

<br>

That's all for the quick start. You are ready to deploy a high-performance cache in your application, with a simple implementation that allows for the creation of truly advanced caching and cache invalidation systems, as that's precisely what this package was created for.

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

```yaml
# config/packages/messenger_cache.yaml
messenger_cache:
    # If You use RefreshAsync (it's message) / refreshAfter (in Cache attribute argument) then
    # you must have declared time of live info that refresh was triggered. It's deleted
    # after succesful refresh. To short value may trigger more than one refresh action for 
    # specific item. Recommended is 10 minutes.
    refresh_triggered_ttl: 600
    # If You need handle cache events like hit, miss, refresh and stamps are not enough for
    # You then change this option to `true`. It add additional events to cache, but it costs performance
    use_events: true
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
        '-1': PBaszak\MessengerCacheBundle\Tests\Helper\Domain\Decorator\LoggingMessageBusDecorator # this one decorator was only for tests. If You want logging decorator You have to make it, but before You start check `use_events`, maybe it will be better option to logging or metrics or anything You want like adding cache result header in response. Just make Your own EventListener ;).
```
Configuration description:
| Parameter | Description | Values |
|----------|-------------|--------|
| `refresh_triggered_ttl` | The amount of time for which information about triggering asynchronous cache content refresh is stored. During this time, another refresh action for this specific cache will not be added to the queue. | `600` seconds, which is the recommended value for most cases. |
| `use_events` | `true` / `false`, where `false` is the recommended value. `true` will add a message bus decorator based on stamps returned by `MessengerCacheManager` and `MessageBusCacheDecorator` (`StampInterface` from `Symfony/Messenger` package). | `false` - by default, <br>`true` - if you create an EventListener for the cache. |
| `pools`  | A list of pools supported by `MessengerCacheBundle`. Only `default` is mandatory, and it will be chosen if you do not specify another pool in the `Cache` attribute. Using aliases is mandatory. Adapters must be services, i.e., you may need to define them in the `cache.yaml` file. An example is provided below the table. | `default: redis`<br>`runtime: runtime`<br>etc. in the form of `$alias: $pool`. |
| `decorated_message_buses` | Cache is not automatically assigned to all message buses in your project. Here you can specify which ones, from the list in the `config/packages/messenger.yaml` file, should support the cache. The default value is `cachedMessage.bus`, which means that naming your constructor argument `MessageBusInterface $cachedMessageBus` is enough to apply the cache. | `- cachedMessage.bus`<br>`- messenger.bus.default`<br>etc., in the form of `$bus` as an array element. |
| `message_bus_decorators` | A list of decorators assigned to all message buses listed in the `decorated_message_buses` section. By default, only `MessageBusCacheDecorator` with a priority equal to `0` is present here. However, if you set the `use_events` option to `true`, the `MessageBusCacheDecorator` will receive a priority of `1`, and before it, with a priority of `0`, the `MessageBusCacheEventsDecorator` will be set. As you probably already understand, the lower the priority value, the faster the selected decorator will handle the `message`, and the one with the highest priority will communicate directly with `MessageBusInterface`. However, note that priorities higher than that assigned to `MessageBusCacheDecorator` should not handle communication with `MessengerCacheManager` but only handle any other process you want to apply before the final `MessageBusInterface`. Normally, other decorators from different libraries will appear before all other decorators resulting from this list. | `"-1": \App\MessageBus\Decorator\MyAwesomeDecorator`<br>As you can see, the convention allows negative priority values, and we recommend using them here. Rule: `"$priority": $decorator |

Example declaration of pools as services:
```yaml
# config/cache.yaml
framework:
    cache:
        default_redis_provider: 'redis://redis:6379'
        pools:
            runtime: 
                adapter: cache.adapter.array
                tags: true
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

Adding a decorator:
```yaml
# config/packages/messenger_cache.yaml
message_bus_decorators:
    "-1": App\Infrastructure\Symfony\Messenger\MessageBusDecorator
```

Decorator example implementation:
```php
# src/Infrastructure/Symfony/Messenger/MessageBusDecorator.php
use PBaszak\MessengerCacheBundle\Stamps\ForceCacheRefreshStamp;

class MessageBusDecorator implements MessageBusInterface
{
    private Request $request;
    private static array $cacheRefreshedMessages;

    public function __construct(
        private MessageBusInterface $decorated,
        RequestStack $requestStack,
    ) {
        $this->request = $requestStack->getMainRequest();
        $this->forceCacheRefresh = $requestStack->getMainRequest()->query->has('forceCacheRefresh');
    }

    /** @param StampInterface[] $stamps */
    public function dispatch(object $message, array $stamps = []): Envelope
    {
        if (!in_array($message, self::$cacheRefreshedMessages) && $this->request->query->has('forceCacheRefresh')) {
            $stamps = array_merge($stamps, [new ForceCacheRefreshStamp()]);
            self::$cacheRefreshedMessages = $message;
        }

        return $this->decorated->dispatch($message, $stamps);
    }
}
```

This parameter passed in the request will cause synchronous refresh of all caches within the request.
```sh
curl --location --request GET 'http://localhost/strings/random?forceCacheRefresh'
```
For more precise solutions, I recommend creating your own methodology for deciding which cache to refresh and not recommending refreshing all possible caches within a single request. Personally, I use a conditional function if ((new ReflectionClass($message))->getShortName() === $this->request->query->get('forceCacheRefresh')) { // add ForceCacheRefreshStamp }. Then an example parameter could look like this: ?forceCacheRefresh=GetUserConfig.

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
