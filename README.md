Watamelo
=====

Watamelo is a small and somehow lightweight PHP MVC Framework under the GNU LGPL licence.

## Requirements

Watamelo was written to use:
* **PHP 5.3** or above
* **pdo** module for PHP (and **pdo_sqlite** for the given example)
* Apache URL rewriting module (currently required, will become optional in the future)

## How to use

You should only add or edit content in the following directories. Everything that is already in it is just an example on how to use watamelo:
* ```app/```: the logic of your project (your application, controllers and models)
* ```data/```: any file related to informations used in your app (routes, database/flat files, any downloadable file) 
* ```tpl/```: the presentation of your project (views, javascript, CSS and any image for your design)

## Example description

### Controllers & Models

The GeneralController and UserManager intend to give you a good view of how to write your own controllers and models.
Note that UserManager elements are used everywhere. Don't delete it even if you intend not to use it. Watamelo will be updated to fix this problem.

### Routing

The ```data/config/routes.json``` file must list all the relative URL for every single page in your app (and for URLs requested through ajax too). In this file, each route can be configured with these options:

```json
"relative/path/to/page/:int|param1:/:string|param2:": {
    "controller": "myControllerName",
    "action": "myMethod",
    "additionalParameters": {
        "json": true
    }
    "optionalParameters": [
        "param3",
        "param4",
    ]
}
```

* ```:int|param1:```: required parameter, must be an integer. Will be accessible withing your code with the name "param1"
* ```:string|param2:```: required parameter, must be an string. Will be accessible withing your code with the name "param2"
* ```"controller"```: required. Name of the controller (without the mention "Controller")
* ```"action"```: required. Name of the controller method (without the mention "Controller")
* ```"additionalParameters"```: optional. Array of fixed parameters that will be accessible from your controller code even if they don't appear in the requested URL. Useful to identify multiple routes pointing to the same action of the same controller.
* ```"optionalParameters"```: optional. Array of optional parameters that appear at the end of the URL, preceded by "/".

Exemple of relative URLs matching the route above :
* ```relative/path/to/page/10/blah/val3|val4```
* ```relative/path/to/page/10/blah/|val4```


### Data

The given example uses a SQLite database (```data/db/watamelo.db```). Its content is described in ```database.sql```. The AuthController, SessionManager and part of the UserManager are used to handle authentication and user sessions.

The ```data/config/config.json``` file contains the parameters required for SessionManager. You can add to it any other parameter.

The ```data/fils/``` folder is intended for files (images, documents) that you wish to make accessible to your users and that don't belong in your design.

### Views & template

Finally, the ```tpl/default/``` directory is designated for your views (RainTPL views), javascript, CSS and images (the ones used in your design). You can create any other directory next to ```default/``` and used it instead by changing the ```template``` key in ```data/config/config.json```.

## Change app & db names

The app name (default: Watamelo) is used in the following. If you wish to change it, you have to modify it in every listed occurence:
* application file name: ```app/watamelo.class.php``` (also change the include in ```index.php```)
* application class name: Watamelo in ```app/watamelo.class.php``` (also called in ```index.php```
* database file name: ```data/db/watamelo.db```

## Change DBMS

Watamelo is currently given with a SQLite example, that can be adapted for any DBMS within the file ```lib/dbfactory.class.php``` and by changing the parameters given when instanciating Managers in ```lib/application.class.php```

## Dependancies

Libraries included in Watamelo:
* [RainTPL](http://www.raintpl.com/) for views
* a modified version of [YosLogin](https://github.com/yosko/yoslogin) (LGPL) for authentication
* [Secure Random Bytes](https://github.com/GeorgeArgyros/Secure-random-bytes-in-PHP/) (New BSD Licence), used in YosLogin

## FAQ

If you have any question or suggestion, please feel free to contact me or post an issue on the [Github page of the project](github.com/yosko/ddb/issues).
