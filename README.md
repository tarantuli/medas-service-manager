# medas-service-manager
Part of the [Medas framework](https://github.com/tarantuli/medas-core).

## Instantiating the service mananger

Create a new instance, passing a closure that returns a `ServiceConfig` object:

````php
new ServiceManager(function (): ServiceConfig {
    $config = new ServiceConfig();

    $config->addPackages([
        ServiceManagerPackage::instance(),
    ]);

    return $config;
});
````

The object automatically registers itself with the global medas() function:

````php
medas()->serviceManager()->resolve(...)
````

## Marking packages

Create a non-service class implementing `Package`, then add the `instance()` method result in the config initialization
closure, as shown in the first example above.

The abstract `BasePackage` class helps to implement the interface. Example of a complete implementation, from this
package itself:

```php
class ServiceManagerPackage extends BasePackage
{
    use AsSingleton;

    public function dependencies(): array
    {
        return [
            CorePackage::instance(),
            ObjectInstantiatorPackage::instance(),
        ];
    }

    public function sourceDirectory(): string
    {
        return __DIR__;
    }

    public function postInstall(): void
    {
        medas()->serviceManager()->primeCaches();
    }
}
```

The `AsSingleton` trait provides the `instance()` method.

> [!NOTE]
> You should place the Package file in the `src` directory of your project, so the `sourceDirectory()` return value
> should be `__DIR__`.

`initialize()` is called when the service manager is instantiated. The `BasePackage` declares an empty method as
a default.

`postInstall()` is called after composer is done updating packages. The `BasePackage` declares an empty method as a
default.

## The service config object

Your packages can register `ParameterResolver` services and `ArgumentProcessor` services
using `$serviceConfig->addParameterResolver()` and `->addArgumentProcessor()` respectively:

```php
public function initialize(ServiceConfig $config): void
{
    $config->addParameterResolver(new ConfigOptionResolver());

    parent::initialize($config);
}
```
