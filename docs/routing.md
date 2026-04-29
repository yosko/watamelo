# Setup routing

[Back to README](../README.md)

Routing definition and use is to be set in method `init()` of your application class. Let's call it `MyApplication`. Exemple:

```php
public function init(Router $router)
{
    // Define routes here
    $router->mapDefault(ErrorHandler::class, 'error404');
    $router->get('/', DefaultHandler::class, 'index');

}

public function execute(Router $router)
{
    // Find and follow the route to the corresponding class method
    $router->dispatch();
}
```

Here we defined a **default route** (`->mapDefault()`) which will be triggered if HTTP request doesn't match any other defined route.

Every other route is defined via the methods:
- either based on HTTP method name: `->get()`, `->post()`, `->put()`, `->delete()`, `->patch()`, `->options()`, `->head()`, `->connect()`, `->trace()`
- or the default `->map()` which requires the HTTP method as an additional string argument

A route needs:
- a source request composed of
  - a HTTP method (either via the PHP method used or via the `->map()` argument)
  - a URL (always starting with a `/`). _Catchall_: If `null` is given, will match any URL (ex: to handle pre-flight request in a single action for all URLs when method `OPTIONS` is called).
- a destination "action" (typically a method within a **Handler** class, which can be a "Controller"):
  - a handler class
  - an action method of that class, which will be called if a HTTP request matches the route
- optional other arguments (see below): *additional* and *optional* parameters

In the example above, a `GET` request to the root (`/`) of the website will call `index()` (a non static method) that can be defined like this in its class (`DefaultHandler`):

```php
public function index()
{
    echo 'Hello, world!';
}
```


### Variable URL parameters (required)
You can setup one or multiple variables in the URL:

```php
$router->get('/test/{a}/{b}', DefaultHandler::class, 'test');
```

And then receive them in the "action" method:
```php
public function test(string $a, string $b)
{
    var_dump($a, $b);
}
```

Note: the type defined in the method's argument will be used for route matching.

Currently supported types: `int`, `string`, `float`, `bool` (backed enum will comme soon).

### Optional URL parameters
You can also define optional URL parameters (will be captured at then end of the URL):

```php
$router->get('/test/{a}/{b}', DefaultHandler::class, 'test')
    ->addOptionalParam('c')
    ->addOptionalParam('d');
```

```php
public function test(string $a, string $b, string $c='Z', string $d='Y')
{
    var_dump($a, $b, $c, $d);
}
```

The following URLs will match the route:
- `/test/A/B`: c and d will have their default values (c=Z, d=Y)
- `/test/A/B/C`: c=C, d=Y (default)
- `/test/A/B/C|D`: c=C, d=D

:warning: **Note**: currently, multiple optional parameters must be separated by a pipe (`|`) instead of a slash (`/`) in the URL.

Note that even if these are named parameters, the optional ones will be matched in the order they were declared.

### Additional parameters
You can set aditional parameters that will be sent to the action method even though they don't appear in the URL (they might be set during application's `->init()` for example):

```php
$router->get('/test', DefaultHandler::class, 'test')
    ->setAdditionalParam('e', 'E');
```

```php
public function test(string $e)
{
    var_dump($e);
}
```

Here, `test()` will always receive `e=E` even if it doesn't come from the HTTP request.
