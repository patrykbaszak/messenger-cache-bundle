# Messenger Cache Bundle #

Cześć! Przedstawiam Ci Messenger Cache Bundle! Paczkę, dzięki której Twoje problemy z obsługiwaniem cache staną się przeszłością.

Zacznijmy jednak od tego, czego ta paczka dla Ciebie nie zrobi:

- nie zdefiniuje za Ciebie serwisów dla adapterów. Każda aplikacja na świecie jest inna i w każdej będzie to wyglądać nieco inaczej. Paczka jedynie potrzebuje nazwy zdefiniowanego serwisu z pliku services.yaml i musisz jej go zapewnić.
- póki co nie obsłuży cache przy użyciu jakieś sprytnej metody, którą możesz sobie wcisnąć w dowolne miejsce w kodzie. Póki co wspierane jest jedynie wpięcie się w metodę dispatch() obiektu MessageBusInterface. 

No więc co ta paczka dla Ciebie zrobi?

- A wiesz co? Lepiej to pokazać!
    ```yaml
    # config/packages/messenger_cache.yaml
    messenger_cache:
        adapters:
            default: 'Symfony\Component\Cache\Adapter\RedisAdapter'
            fastest: 'Symfony\Component\Cache\Adapter\PhpArrayAdapter'
            runtime: 'Symfony\Component\Cache\Adapter\ArrayAdapter'
    ```

    ```php
    # src/Application/Query/GetRandomString.php
    use PBaszak\MessengerCacheBundle\Attribute\Cache;
    use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;

    #[Cache(adapter: 'runtime')]
    class GetRandomString implements Cacheable
    {
    }
    ```
    i w sumie to już! Teraz, jeśli wywołasz powyższe zapytanie w dowolnym miejscu w kodzie z wykorzystaniem obiektu `MessageBus`, to odpowiedź będzie przechowana w pamięci w ramach obecnie wykonywanego żądania.

    Adaptery definiujesz sam i tylko adapter `default` jest wymagany - paczka z niego skorzysta za każdy razem, w którym nie zdefiniujesz argumentu `adapter` w atrybucie `Cache`. Czyli w powyższym przykładzie byłby to Redis!

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
