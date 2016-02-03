<?php
/**
 * Watamelo Framework - lightweight MVC framework
 *
 * @license     LGPL v3 (http://www.gnu.org/licenses/lgpl.html)
 * @author      Yosko <webmaster@yosko.net>
 * @version     see WATAMELO_VERSION in lib/abastractapplication.class.php
 *              APP_VERSION below is for the application version number, whereas WATAMELO_VERSION is for the framework version
 * @link        https://github.com/yosko/watamelo
 */

use Watamelo\App;

define( 'DEVELOPMENT_ENVIRONMENT', true );
define( 'APP_VERSION', '1.0' );
define( 'ROOT', dirname(__FILE__) );
define( 'DB_PATH', ROOT.'/data/db/' );

//include the app class
require_once( ROOT.'/app/watamelo.class.php');

//start the app
$app = new \Watamelo\App\Watamelo('Wata');
$app->run();

?>