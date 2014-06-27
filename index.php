<?php
/**
 * Watamelo Framework - lightweight MVC framework
 *
 * @license     LGPL v3 (http://www.gnu.org/licenses/lgpl.html)
 * @author      Yosko <webmaster@yosko.net>
 * @version     see WATAMELO_VERSION in lib/application.class.php
 * @link        https://github.com/yosko/watamelo
 */

define( 'DEVELOPMENT_ENVIRONMENT', true );
define( 'APP_VERSION', '1.0' );
define( 'ROOT', dirname(__FILE__) );

//include the app class
require_once( ROOT.'/app/watamelo.class.php');

//start the app
$app = new Watamelo;
$app->run();

?>