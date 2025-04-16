Watamelo
=====

Watamelo is a small and rather lightweight PHP MVC Framework under the GNU LGPL licence.

It is currently a work in progress. This means that it might be unstable, and every way of doing things is subject to change in future versions.

## Requirements

Watamelo requires:

* **PHP 8** or above
* Apache URL rewriting module (although it might be easy to work with other web server's rewriting approach)

## Install the framework via Composer
You can use either start from the [skeleton repo](https://github.com/yosko/watamelo-skeleton) (see instructions there) or start from scratch

### Start from scratch
First, set your `composer.json`:

```json
{
    ...
    "require": {
        "yosko/watamelo": "dev-master"
    },
    "repositories": [
        {
            "type": "git",
            "url": "git@github.com:yosko/watamelo.git"
        }
    ]
}
```

Then install the framework:

```bash
composer install
```

## Define app

You will then need to define:
- your own application class extending `\Watamelo\AbstractApplication`, with:
  - routing implementation and any initialization in `init()`
  - routing execution in `execute()`
- your single entry point (example: `index.php`) instaciating your app class and calling `run()` on it.


## Setup routing
As stated above, routing definition and use is to be set in method `init()` of your application class. Let's call it `MyApplication`. Exemple:

```php
public function init(Router $router)
{
    // Define routes here
    $router->mapDefault(ErrorController::class, 'error404');
    $router->get('/', DefaultController::class, 'index');

}

public function init(Router $router)
{
    // Find and follow the route to the corresponding class method
    $route->dispatch();
}
```

Here we defined a **default route** (`->mapDefault()`) which will be triggered if HTTP request doesn't match any other defined route.

Every other route is defined via the methods:
- either based on HTTP method name: `->get()`, `->post()`, `->put()`, `->delete()`, `->patch()`, `->options()`, `->head()`, `->connect()`, `->trace()`
- or the default `->map()` which requires the HTTP method as an additional string argument

A route needs:
- a source request composed of
  - a HTTP method (either via the PHP method used or via the `->map()` argument)
  - a URL (always starting with a `/`)
- a destination "action":
  - a class
  - a method of that class, which will be called if a HTTP request matches the route
- optional other arguments (see below): *additional* and *optional* parameters

In the example above, a `GET` request to the root (`/`) of the website will call `index()` (a non static method) that can be defined like this in its class (`DefaultController`):

```php
public function index()
{
    echo 'Hello, world!';
}
```


### Variable URL parameters (required)
You can setup one or multiple variables in the URL:

```php
$router->get('/test/{a}/{b}', DefaultController::class, 'test');
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
$router->get('/test/{a}/{b}', DefaultController::class, 'test')
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
- `/test/A/B/C/D`: c=C, d=D

Note that even if these are named parameters, the optional ones will be matched in the order they were declared.

### Additional parameters
You can set aditional parameters that will be sent to the action method even though they don't appear in the URL (they might be set during application's `->init()` for example):

```php
$router->get('/test', DefaultController::class, 'test')
    ->setAdditionalParam('e', 'E');
```

```php
public function test(string $e)
{
    var_dump($e);
}
```

Here, `test()` will always receive `e=E` even if it doesn't come from the HTTP request.

### Views & template
TODO: currently, this feature is incomplete and cannot be used. The View instance is only known by the app class and isn't sent to controllers/actions.

Watamelo gives a templating system: not a real template engine as it works with templates in PHP, but it still sets a separate execution context.

You can choose your own templates path (default : `src/Templates/`) by setting it during init:

```php
public function init(Router $router)
{
  $this->setTplPath('my/custom/templating/path/');
}
```

## FAQ

If you have any question or suggestion, please feel free to contact me or post an issue on the [Github page of the project](https://github.com/yosko/watamelo/issues).

## Version History

- v1.2 (2025-04-16)
  - hopefully *final* reorganization of namespaces, directories and classes names
  - simplified base namespace to "Watamelo" and framework's to "Watamelo\Framework"
  - Request and Method will their own autonomous component (might turn it into a separate package later)
  - Http handlers ("controllers") are now called via an invoker which can be replaced/overwritten
- v1.1 (2025-04-12)
  - reworked and moved HttpRequest to its own namespace/directory
  - removed AbstractComponent to avoid injecting whole AbstractApplication to everyone
  - removed constants and simplified paths definition
  - routing: rewritten path isn't sent as a query param anymore
  - routing: handlers can be class names or instances
  - multiple fixes from previous version
- v1.0-rc1 (2025-03-20)
  - reworked HTTP request/response handling and routing
    - new HttpRequest and HttpResponse classes
    - routes declaration based on method name
    - actions now receive parameters (required, optional, additional) as named arguments
    - route parameters now support types: int, string, float, bool ((backed)enum will come)
  - simplified View class (most features should be handled as an extension)
  - cleanup of convoluted or unused features
- v0.12 (2022-09-16)
  - migrated to PHP 7.4
  - enabled strict types
  - cleaned/formatted code
  - updated external libraries
  - added Data* and other facilitators in example app
- v0.11 (2016-11-14)
  - fixed cookie path errors
  - minor rewrite and new useful functions in example classes
  - fixed shorthand function ```executeTransaction``` to avoid transaction conflicts
  - fixed missing explicit namespaces
  - fixed syntax error in ```lib/Application.php```
- v0.10 (2016-02-04)
  - introduction of namespace (now used everywhere)
  - minor tweaks to .gitignore
  - proper HTTPS handling in generated URLs
  - auto create long-term sessions directory
  - new $currentUrl parameters available to views
  - fixed content-type headers for atom/rss
  - exceptions and errors handling and logging
  - lots of tweaking to comply to PSR-2
  - edited autoloader to handle potential plugin classes
- v0.8/0.9 (2015-06-30)
  - added rewrite rule to block access to ```lib/``` directory
  - added the ability to use template files stored outside of the ```tpl/``` directory
  - expanded ConfigManager to handle custom json config files
  - added a SqlGenerator class to construct queries and updated UserManager to rely on it
  - added a bunch of small utility functions to the Tools class
  - automatically rename error log files every day
  - updated example database model for a simpler naming system
  - updated EasyDump to 0.8 (including latest fixes)
  - disabled authentication log (not very useful)
  - minor bugfixes
- v0.8 (2014-09-22)
  - added CSS views handling (a way to add PHP in CSS files to handle variables, etc...)
  - use StdClass objects instead of arrays for list of objects of variable natures
  - handle DBMS and DB parameters in example code, not in Watamelo library (gives the ability to use as much database connections as needed)
  - fix error on renderData() where options didn't work
  - in given example, moved SQL files into the model directory
  - in given example, updated YosLogin (this never stops...)
  - in given example, use prefix for table names
  - in given example, keep more useful tools
  - in given example, make authController (and its YosLogin logger) accessible
- v0.7 (2014-06-27)
  - handle app variables within routes
  - updated YosLogin, again
  - make URL parameters accessible from the view by default
  - avoid having to redeclare executeIndex in every controller
  - minor fixes in config manager
  - enhanced session manager with some other methods
  - remove example specific code from the core of watamelo
- v0.6 (2014-02-17)
  - parameters are extracted for the view, instead of kept in an array
  - reorganised some code
  - define DBMS on the app level
  - updated YosLogin
  - separated app version number from framework version number
- v0.5 (2013-10-28)
  - handle views without any external template librarby
  - minor fixes
- v0.4 (2013-10-17)
  - many minor fixes and tweaks
  - replaced route definition file from JSON to XML (with DTD)
  - moved route definition file from data/ to app/ (because it concerns the app itself)
- v0.3 (2013-08-12)
  - added feed generation (atom/rss)
  - added data download as file
- v0.2 (2013-08-02)
  - handle urls with or without rewriting
  - config handling is now a Controller (and considered as implementation example)
- v0.1 (2013-08-01)
  - initial version