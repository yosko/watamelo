<?php
/**
 * Watamelo Framework - lightweight MVC framework
 *
 * @license     LGPL v3 (http://www.gnu.org/licenses/lgpl.html)
 * @author      Yosko <webmaster@yosko.net>
 * @version     v0.3
 * @link        https://github.com/yosko/watamelo
 */

define( 'DEVELOPMENT_ENVIRONMENT', true );
define( 'VERSION', '0.3' );
define( 'ROOT', dirname(__FILE__) );

//include the app class
require_once( ROOT.'/app/watamelo.class.php');

//start the app
$app = new Watamelo;
$app->run();

?>