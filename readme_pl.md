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
        '-1': PBaszak\MessengerCacheBundle\Tests\Helper\Domain\Decorator\LoggingMessageBusDecorator
```
Opis konfiguracji:
| Parametr | Opis | Wartości |
|----------|------|----------|
| `refresh_triggered_ttl` | Czas przechowywania informacji o tym, że asynchroniczna akcja odświeżenia zawartości cache została uruchomiona. W tym czasie nie zostanie dodana do kolejki kolejna akcja odświeżenia tego konkretnego cache. | `600` sekund, które są wartością rekomendowaną dla większości przypadków. |
| `use_events` | `true` / `false`, gdzie `false` jest wartością rekomendowaną. `true` spowoduje dodanie message bus dekoratora, który na podstawie zwróconych przez `MessengerCacheManager` oraz `MessageBusCacheDecorator` stampów (`StampInterface` z paczki `Symfony/Messenger`) | `false` - domyślnie, <br>`true` - jeśli tworzysz EventListenera dla cache. |
| `pools`  | Lista poolów obsługiwanych przez `MessengerCacheBundle`, obowiązkowy jest tylko `default`, który to będzie wybrany, jeśli w atrybucie `Cache` nie wskażesz innego poola. Używanie aliasów jest obowiązkowe. Adaptery muszą być serwisami, tj. możliwe, że będziesz musiał je zdefiniować w pliku `cache.yaml`, przykład poniżej tabelki. | `default: redis`<br>`runtime: runtime`<br>itd. w konwencji `$alias: $pool`. |
| `decorated_message_buses` | Cache nie jest z automatu przypisany do wszystkich message busy w Twoim projekcie. W tym miejscu możesz ustalić, które, sposród listy zawartej w pliku `config/packages/messenger.yaml` mają wspierać cache. Domyślna wartość to `cachedMessage.bus` i sprawia ona, że wystarczy, że nazwiesz argument swojego konstruktora `MessageBusInterface $cachedMessageBus`, aby została zastosowana. | `- cachedMessage.bus`<br>`- messenger.bus.default`<br>itd. w konwencji: `$bus` jako element tablicy. |
| `message_bus_decorators` | Lista dekoratorów przypisanych do wszystkich message busy wypisanych w punkcie `decorated_message_buses`. Domyślnie jest tutaj tylko `MessageBusCacheDecorator` z priorytetem równym `0`. Jednak, jeśli ustawisz opcje `use_events` na `true` to `MessageBusCacheDecorator` otrzyma priorytet `1`, a przed nim, z priorytetem `0` zostanie ustawiony `MessageBusCacheEventsDecorator`. Jak już pewnie rozumiesz, im niższa wartość priorytetu tym szybciej wybrany dekorator będzie obsługiwał `message`, a ten o najwyższym priorytecie będzie komunikował się bezpośrednio z `MessageBusInterface`. Zwróć jednak uwagę, że priorytety wyższe niż ten przypisany do `MessageBusCacheDecorator` nie powinny obsługiwać komunikacji z `MessengerCacheManager`, a jedynie obsługiwać dalszy dowolny proces, który chcesz zastosować przed ostatecznym `MessageBusInterface`. Standardowo inne dekoratory, z innych bibliotek najczęściej pojawią się przed wszystkimi innymi dekoratorami wynikającymi z tej listy. | `"-1": \App\MessageBus\Decorator\MyAwesomeDecorator`<br>jak widzisz konwencja dopuszcza wartości ujemne priorytetów i takie tutaj rekomendujemy. Zasada: `"$priorty": $decoratorClassString` |

Przykładowa deklaracja poolów jako serwisów:
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

### **Przykład nr 3 (ForceCacheRefreshStamp)** ###

Dodawanie dekoratora:
```yaml
# config/packages/messenger_cache.yaml
message_bus_decorators:
    "-1": App\Infrastructure\Symfony\Messenger\MessageBusDecorator
```

Implementacja przykładowej dekoratora:
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

Tak przekazany parametr żądania spowoduje synchroniczne odświeżanie wszystkich cache w ramach żądania.
```sh
curl --location --request GET 'http://localhost/strings/random?forceCacheRefresh'
```
Dla bardziej precyzyjnych rozwiązań rekomenduję stworzenie własnej metodyki decydowania o tym, który cache należy odświeżyć i nierekomenduję odświeżania wszystkich możliwych cache w ramach jednego żądania. Osobiście stosuję funkcje warunkową `if ((new ReflectionClass($message))->getShortName() === $this->request->query->get('forceCacheRefresh')) { // add ForceCacheRefreshStamp }`, wtedy przykładowy parametr może wyglądać następująco: `?forceCacheRefresh=GetUserConfig`.

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

## Ustawienia szczegółowe ##

### **Atrybuty** ###

### Cache ###

| Parametr | Opis |
|:---------|:-----|
| `$ttl` | Czas życia cache w sekundach. Możesz też się zainteresować interfejsem `DynamicTtl`, który pozwoli Ci dynamicznie wybrać `ttl` dla cache. |
| `$refreshAfter` | Czas ważności cache w sekundach, po upłynięciu tego czasu, po kolejnym wywołaniu `Bundle` spróbuje odświeżyć ten cache. **UWAGA: Konieczne jest dodanie `PBaszak\MessengerCacheBundle\Message\RefreshAsync` do Twojego systemu kolejek opartego o `MessageBusInterface` (AMQP, Redis, Doctrine - zobacz paczki: `Symfony/amqp-messenger`, `Symfony/redis-messenger`, `Symfony/doctrine-messenger`)**. |
| `$pool` | Alias poolu, który zostanie użyty do obsługi cache. |
| `$tags` | Lista stałych tagów dla danego zasobu, ale może będziesz zainteresowany interfejsem `DynamicTags`, który pozwoli Ci na pełną customizację. |


### Invalidate ###

| Parametr | Opis |
|:---------|:-----|
| `$tags` | Lista stałych tagów, które mają zostać poddane inwalidacji. Możesz je zastąpić przez interfejs `DynamicTags` |
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
| `UniqueHash` | Metoda `getUniqueHash(): string` pozwala Ci samodzielnie zdefiniować hash dla instancji `Message`. Możesz tego użyć zamiast `HashableInstance`. |
