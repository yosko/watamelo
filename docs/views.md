# Views & template

[Back to README](../README.md)

:warning: *@todo*: currently, this feature is incomplete and should not be used. The View instance is only known by the app class and isn't sent to handlers/actions.

Watamelo gives a templating system: not a real template engine as it works with templates in PHP, but it still sets a separate execution context.

You can choose your own templates path (default : `src/templates/`) by setting it during init:

```php
public function init(Router $router)
{
  $this->setTplPath('my/custom/templating/path/');
}
```
