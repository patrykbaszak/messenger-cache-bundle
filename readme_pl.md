# Messenger Cache Bundle #

## Instalacja ##

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

## Konfiguracja ##

Utwórz lub skopiuj plik `messenger_cache.yaml`:
```sh
# kopiowanie
cp vendor/pbaszak/messenger-cache-bundle/config/packages/messenger_cache.yaml config/packages/messenger_cache.yaml
#
# lub
#
# tworzenie
touch config/packages/messenger_cache.yaml
```
Zalecane wstępne ustawienia:
```yaml
# config/packages/messenger_cache.yaml
messenger_cache:
    pools:
        default: filesystem
        runtime: runtime
        redis: redis
```
Opis konfiguracji:
| Parametr | Opis |
|----------|------|
| `messenger_cache.pools`  | Lista poolów obsługiwanych przez `MessengerCacheBundle`, obowiązkowy jest tylko `default`, który to będzie wybrany, jeśli w atrybucie `Cache` nie wskażesz innego poola. Używanie aliasów jest obowiązkowe. Adaptery muszą być serwisami, tj. możliwe, że będziesz musiał je zdefiniować w pliku `services.yaml`, przykład poniżej tabelki. |

Przykładowa deklaracja poolów jako serwisów:
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
**UWAGA: W twoim kodzie może (a raczej na pewno będzie) to wyglądać trochę inaczej.**

Istnieją jeszcze dwie komendy:
 - `PBaszak\MessengerCacheBundle\Message\InvalidateAsync`,
 - `PBaszak\MessengerCacheBundle\Message\RefreshAsync`,

musisz je dodać do obsługi asynchronicznej samodzielnie. Jeśli tego nie zrobisz, będą wykonywane synchronicznie, co wpłynie na wydajność Twojej aplikacji przy pobieraniu danych z cache.

<hr>
<hr>

## Użycie ##

### **Przykład nr 1 (Cache)** ###

Przykładowa klasa obsługiwana przez `Symfony Messenger` jako `Message`, posiada swój `Handler`, który za każdym razem zwraca losowy ciąg znaków.

```php
# src/Application/Query/GetRandomString.php
use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;

#[Cache()]
class GetRandomString implements Cacheable
{
}
```

Dowolny Manager, który wywołuje interesujący nas `Message` i zwraca jego odpowiedź:

```php
# src/Domain/Manager/StringsManager.php
use App\Application\Query\GetRandomString;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\HandleTrait;

class StringsManager
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
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

### **Przykład nr 2 (CacheInvalidation)** ###

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

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
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

### **Przykład nr 3 (ForceCacheRefreshStamp)** ###

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
            $stamps += [new ForceCacheRefreshStamp()];
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

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
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

## Ustawienia szczegółowe ##

### **Atrybuty** ###

### Cache ###

| Parametr | Opis |
|:---------|:-----|
| `$ttl` | Czas życia cache w sekundach. Możesz też się zainteresować interfejsem `DynamicTtl`, który pozwoli Ci dynamicznie wybrać `ttl` dla cache. |
| `$refreshAfter` | Czas ważności cache w sekundach, po upłynięciu tego czasu, po kolejnym wywołaniu `Bundle` spróbuje odświeżyć ten cache. **UWAGA: Konieczne jest dodanie `PBaszak\MessengerCacheBundle\Message\RefreshAsync` do Twojego systemu kolejek opartego o `MessageBusInterface` (AMQP, Redis, Doctrine - zobacz paczki: `Symfony/amqp-messenger`, `Symfony/redis-messenger`, `Symfony/doctrine-messenger`)**. |
| `$pool` | Alias poolu, który zostanie użyty do obsługi cache. |
| `$group` | Jest to tag, który jest używany ściśle do wiązania wielu cache w jedną grupę (ściśle współpracuje z interfejsem `OwnerIdentifier`, co pozwala sprecyzować przynależność cache do grupy, której właścicielem jest wskazany `owner`.) |
| `$tags` | Lista stałych tagów dla danego zasobu, ale może będziesz zainteresowany interfejsem `DynamicTags`, który pozwoli Ci na pełną customizację. |
| `$useOwnerIdentifierForTags` | Wartość `true` sprawi, że tagi będą posiadały przypisany prefix z wartością zwracaną przez metodę `getOwnerIdentifier` interfejsu `OwnerIdentifier`. Tag będzie wyglądać tak: `_{ownerIdentifier}_{tag}`. |

### Invalidate ###

| Parametr | Opis |
|:---------|:-----|
| `$tags` | Lista stałych tagów, które mają zostać poddane inwalidacji. Możesz je zastąpić przez interfejs `DynamicTags` |
| `$useOwnerIdentifierForTags` | Domyślnie `false`. Wartość `true` sprawi, że tagi `$tags` będą posiadały prefix zwrócony przez metodę `getOwnerIdentifier` interfejsu `OwnerIdentifier` |
| `$groups` | Lista grup (tagów), które mają zostać poddane inwalidacji. |
| `$useOwnerIdentifier` | Domyślnie `true`. Działa analogicznie jak `$useOwnerIdentifierForTags`, tylko, że wobec `$groups`. |
| `$pool` | Adapter, który ma zostać poddany inwalidacji, jeśli `null`, to wszystkie pooly `TagAwareAdapterInterface` będą poddane inwalidacji. |
| `$invalidateBeforeDispatch` | Jeśli potrzebujesz, inwalidacja cache może odbyć się przed wykonaniem właściwego `Message`. |
| `$invalidateOnException` | Jeśli obsługa `Message` zakończy się wystąpieniem wyjątku, to inwalidacja po wykonaniu `Message` nadal może zostać wykonana, jeśli tylko chcesz. |
| `$invalidateAsync` | Jeśli `true` oraz `PBaszak\MessengerCacheBundle\Message\InvalidateAsync` znajduje się w Twoim systemie kolejek (`Symfony/amqp-messenger`, `Symfony/redis-messenger`, `Symfony/doctrine-messenger`) jako wykonywane asynchronicznie, to inwalidacja cache odbędzie się asynchronicznie. |

### **Opcjonalne interfejsy** ###

| Interfejs | Opis |
|:----------|:-----|
| `CacheableCallback` | Metoda `isCacheable(): bool` pozwoli Ci dynamicznie zdecydować o użyciu bądź nie cache. |
| `DynamicTags` | Metoda `getDynamicTags(): array` pozwoli Ci dynamicznie dostarczyć tagi do obsługi cache (zastępuje właściwość `Cache(tags: [])`). |
| `DynamicTtl` | Metoda `getDynamicTtl(): int` pozwoli Ci dynamicznie dostarczyć wartość `ttl` w sekundach dla cache. |
| `HashableInstance` | Metoda `getHashableInstance(): Cacheable` powinna zwrócić obiekt `Message` w formie, którą można poddać hashowaniu. Możesz musieć użyć tego interfejsu, jeśli cache, który chcesz przechować powinien być dostępny dla wielu użytkowników i jednocześnie w `Message` posiadasz kontekst użytkownika. Dzięki tej metodzi pozbędziesz się go. |
| `OwnerIdentifier` | Metoda `getOwnerIdentifier(): string` pozwoli Ci przypisać cache do dowolnego właściciela opisanego przez `string`, który ów metoda zwraca. |
| `UniqueHash` | Metoda `getUniqueHash(): string` pozwala Ci samodzielnie zdefiniować hash dla instancji `Message`. Możesz tego użyć zamiast `HashableInstance`. |
