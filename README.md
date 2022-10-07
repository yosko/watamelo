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
* `Config/`:  where all configuration files related to Watamelo and its plugins must be located. If you choose another directory, you must specify its path when instancianting your `AbstractApplication`.
* ```data/```: any file related to informations used in your app (database/flat files, any downloadable file)
* ```tpl/```: the presentation of your project (views, javascript, CSS and any image for your design)

## Example description

### Controllers & Models

* ```GeneralController```: example of a classic controller, used to display most of the views existing in the example
* ```User``` and ```UserManager```: example of a classic manager using queries
* ```UserLevel``` and ```UserLevelManager```
* ```ConfigManager```: example of a classic manager using JSON files
* ```AuthController```, ```SessionManager``` and part of the ```UserManager``` are used to handle authentication and user sessions here.

* ```SqlGenerator```: utility class to build SQL queries
* ```Bread```, ```BreadManager``` and ```BreadView```: generic classes allowing CRUD actions with configuration instead of code.
* ```SqlManager```: generic class handling the use of base SQL for CRUD actions
* ``````: 

### Data

The given example uses a SQLite database (```data/db/watamelo.db```). Its content is described in ```app/model/database.sql```.

The ```data/config/config.json``` file contains the parameters required for SessionManager. You can add to it any other parameter.

The ```data/files/``` folder is intended for files (images, documents) that you wish to make accessible to your users and that don't belong in your design.

### Views & template

Finally, the ```tpl/default/``` directory is designated for your views, javascript, CSS and images (the ones used in your design). You can create any other directory next to ```default/``` and used it instead by changing the ```template``` key in ```data/config/config.json```.

## Routing

### Route definition

The ```routes.xml``` file must list all the relative URL for every single page in your app (and for URLs requested through ajax too). It must be located in the configuration directory. In this file, each route can be configured with these options:

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
* ```<optional/>```: optional, can be used multiple times. Defines an optional parameter that appear at the end of the URL, preceded by "/". Multiple optional parameters are then separated by "|". You can still use GET parameters at the end of your URLs with ```?param5=val5```, but these don't need to be declared in ```routes.xml```.

Exemple of relative URLs matching the route above :

* ```relative/path/to/page/10/blah/```
* ```relative/path/to/page/29/bleh/val3|val4```
* ```relative/path/to/page/43/bloh/|val4?param5=val5```

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
* in ```data/config/config.global.json```, change the parameter **ApacheURLRewriting** to false, or replace its calls in ```app/Watamelo.php``` by the boolean of your choice.

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

The app name (default: Wata) may be used in the project. To change it, you just have to change the value sent to the constructor of Watamelo in ```index.php```.

As for the sqlite database file (```data/db/watamelo.db```) and session name, their name is defined within the application through the key ```sessName``` of the config file ```data/config/config.json```.

## Change DBMS

Watamelo is currently given with a SQLite example, that can be adapted for any DBMS within the file ```lib/DbFactory.php``` and by changing the parameters given when instanciating Managers in ```lib/Application.php```.

In a future version, it will be configurable without having to change the core's code.

## Dependancies

Watamelo doesn't have any dependancies.

But the example code given relies on these libraries:

* [EasyDump](https://github.com/yosko/easydump) (LGPL) for displaying variables content (debug)
* [YosLogin](https://github.com/yosko/yoslogin) (LGPL) for authentication
* [Secure Random Bytes](https://github.com/GeorgeArgyros/Secure-random-bytes-in-PHP/) (New BSD Licence), used in example for authentication and password hashing. Can be removed.

## FAQ

If you have any question or suggestion, please feel free to contact me or post an issue on the [Github page of the project](https://github.com/yosko/watamelo/issues).

## Version History

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