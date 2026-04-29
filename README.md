Watamelo
=====
[![Latest Stable Version](https://img.shields.io/packagist/v/yosko/watamelo.svg)](https://packagist.org/packages/yosko/watamelo)
[![License](https://img.shields.io/packagist/l/yosko/watamelo.svg)](https://packagist.org/packages/yosko/watamelo)

Watamelo is a small and rather lightweight PHP Framework under the GNU LGPL licence.

It is currently a work in progress. This means that it might be unstable, and every way of doing things is subject to change in future versions.

## Requirements

Watamelo requires:

* **PHP 8.2** or above
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
    }
}
```
*Or you can use any tagged version such as "^1.3" instead of "dev-master".*

Then install the framework:

```bash
composer install
```

## Quick Start

The easiest way to start is using the [watamelo-skeleton](https://github.com/yosko/watamelo-skeleton) repository.

Otherwise, you can create a minimalist application in 3 steps:

1. **Define your App** (here an example in `src/App.php`):
```php
class App extends \Watamelo\Framework\AbstractApplication {
    public function init($router) {
        $router->get('/', \App\Controller\MainController::class, 'hello');
    }
    public function execute($router) { $router->dispatch(); }
}
```

2. **Create your action** (typically in a **Handler** class such as a "Controller"; here an example in `src/Controller/MainController.php`):
```php
class MainController {
    public function hello() { echo "Hello Watamelo!"; }
}
```

3. **Launch it** (here an example in `public/index.php`):
```php
(new \App\App())->run();
```

---

## Documentation

See specific documentation files for more detailed information:

- [Defining your Application](docs/application.md)
- [Setup Routing](docs/routing.md)
- [Views & Templates](docs/views.md)
- [Version History (Changelog)](docs/changelog.md)

## FAQ

If you have any question or suggestion, please feel free to contact me or post an issue on the [Github page of the project](https://github.com/yosko/watamelo/issues).