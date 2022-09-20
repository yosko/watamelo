<?php
/**
 * Watamelo Framework - lightweight MVC framework
 *
 * @license     LGPL v3 (http://www.gnu.org/licenses/lgpl.html)
 * @author      Yosko <webmaster@yosko.net>
 * @version     see WATAMELO_VERSION in lib/Application.php
 *              APP_VERSION below is for the application version number, whereas WATAMELO_VERSION is for the framework version
 * @link        https://github.com/yosko/watamelo
 */

declare(strict_types=1);

use Watamelo\App\Watamelo;

define('DEVELOPMENT_ENVIRONMENT', true);
define('APP_VERSION', '1.0');
define('ROOT', dirname(__FILE__));
define('DB_PATH', ROOT . '/data/db/');

//include autoloader
require_once(ROOT . '/vendor/autoload.php');

//start the app
$app = new Watamelo('Wata');
$app->run();
