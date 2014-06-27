Watamelo
=====

Watamelo is a small and rather lightweight PHP MVC Framework under the GNU LGPL licence.

It is currently in Alpha. This means that it might be unstable, and every way of doing things is subject to change in future versions.

## Requirements

Watamelo was written to use:

* **PHP 5.3** or above

The given example code also requires these to work:

* **pdo** (PHP module)
* **pdo_sqlite** (PHP module)

Optional (but recommended) elements:

* Apache URL rewriting module

## How to use

You should only add or edit content in the following directories. Everything that is already in it is just a complete example on how to use watamelo:

* ```app/```: the logic of your project (your application, controllers, models and route definitions)
* ```data/```: any file related to informations used in your app (database/flat files, any downloadable file) 
* ```tpl/```: the presentation of your project (views, javascript, CSS and any image for your design)

## Example description

### Controllers & Models

* ```GeneralController```: example of a classic controller, used to display most of the views existing in the example
* ```UserManager```: example of a classic manager using SQL
* ```ConfigManager```: example of a classic manager using JSON files
* ```AuthController```, ```SessionManager``` and part of the ```UserManager``` are used to handle authentication and user sessions here.

### Data

The given example uses a SQLite database (```data/db/watamelo.db```). Its content is described in ```app/utils/database.sql```.

The ```data/config/config.json``` file contains the parameters required for SessionManager. You can add to it any other parameter.

The ```data/files/``` folder is intended for files (images, documents) that you wish to make accessible to your users and that don't belong in your design.

### Views & template

Finally, the ```tpl/default/``` directory is designated for your views, javascript, CSS and images (the ones used in your design). You can create any other directory next to ```default/``` and used it instead by changing the ```template``` key in ```data/config/config.json```.

## Routing

### Route definition

The ```app/routes.xml``` file must list all the relative URL for every single page in your app (and for URLs requested through ajax too). In this file, each route can be configured with these options:

```xml
<route path="relative/path/to/page/:int|param1:/:string|param2:" controller="myControllerName" action="myMethod">
  <additional name="json" value="true"/>
  <optional name="param3"/>
  <optional name="param4"/>
</route>
```

* ```:int|param1:```: required parameter, must be an integer. Will be accessible within your code with the name "param1"
* ```:string|param2:```: required parameter, must be an string. Will be accessible within your code with the name "param2"
* ```controller=```: required. Name of the controller (without the suffix "Controller")
* ```action=```: required. Name of the controller method (without the prefix "execute")
* ```"<additional/>"```: optional, can be used multiple times. Defines a parameter with a fixed value that will be accessible from your controller code even if it doesn't appear in the requested URL. Useful to identify multiple routes pointing to the same action of the same controller.
* ```<optional/>```: optional, can be used multiple times. Defines an optional parameter that appear at the end of the URL, preceded by "/". Multiple optional parameters are then separated by "|".

Exemple of relative URLs matching the route above :

* ```relative/path/to/page/10/blah/```
* ```relative/path/to/page/29/bleh/val3|val4```
* ```relative/path/to/page/43/bloh/|val4```

### Route with app variable

In the above example path, there are parts that are fixed (such as ```relative/path/to/page/```), and parts that can vary a lot
(such as ```10/blah```) because they might be defined on a context/data/user level.

But what if you need to define some path parts that can evolve but are not subject to change often, parts that you want to
define on your application level? For exemple, you would like to make the admin section of your website accessible via a
not so obvious URL, and change it sometimes for security reasons without having to edit tons of routes in ```routes.xml```.

For this kind of use, you can declare a variable in your app's main class which will be sent to the Router in the ```$variables``` array. The route syntax is close to parameters, without the pipe (```|```):

```xml
<route path="fixed-path/:variable-path:/" controller="myControllerName" action="myMethod" />
```

This way, ```:variable-path:``` will be automatically replaced by the value of ```$variables['variable-path']```. See the
example given for the admin route.

### Routing method: basic or Apache Rewriting

The default example is defined to use Apache **rewrite_mod**. If you don't want to use it or just can't, just do the following: 

* delete or rename the ```.htaccess``` file at the project root
* in ```data/config/config.global.json```, change the parameter **ApacheURLRewriting** to false, or replace its calls in ```app/watamelo.class.php``` by the boolean of your choice.

The routes are always put in the **GET** parameter called **url** (whether you use basic or Apache Rewriting method):

* Url example with Apache rewriting: ```http://www.example.com/my-app/relative/path/to/page/10/blah/val3|val4```
* Url example without Apache rewriting: ```http://www.example.com/my-app/?url=relative/path/to/page/10/blah/val3|val4```

You can change the name of this parameter by calling in the ```run()``` of your app this way:

```php
$router = new Router($this);
$router->setGetParamName('customParamName');
```

There is no route builder, but to avoid problems in your views, there are are three variables you can use :

* ```$rootUrl```: always point to the root of your website (let's say it is ```http://www.example.com/```).
* ```$baseUrl```: same as ```$rootUrl```, it changes to ```http://www.example.com/?url=``` (or to the custom parameter name you used) if rewriting is disabled.
* ```$templateUrl```: path to the current template (by default: ```http://www.example.com/tpl/default/```).

## Change app & db names

The app name (default: Watamelo) is used in the following. If you wish to change it, you have to modify it in every listed occurence:

* application file name: ```app/watamelo.class.php``` (also change the include in ```index.php```)
* application class name: Watamelo in ```app/watamelo.class.php``` (also called in ```index.php```)
* database file name: ```data/db/watamelo.db``` (lower case)

## Change DBMS

Watamelo is currently given with a SQLite example, that can be adapted for any DBMS within the file ```lib/dbfactory.class.php``` and by changing the parameters given when instanciating Managers in ```lib/application.class.php```.

In a future version, it will be configurable without having to change the core's code.

## Dependancies

Watamelo doesn't have any dependancies.

But the example code given relies on these libraries:

* [YosLogin](https://github.com/yosko/yoslogin) (LGPL) for authentication
* [Secure Random Bytes](https://github.com/GeorgeArgyros/Secure-random-bytes-in-PHP/) (New BSD Licence), used in example for authentication and password hashing. Can be removed.

## FAQ

If you have any question or suggestion, please feel free to contact me or post an issue on the [Github page of the project](https://github.com/yosko/watamelo/issues).

## Version History

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