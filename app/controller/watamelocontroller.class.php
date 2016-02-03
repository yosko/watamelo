<?php
namespace Watamelo\Controllers;

/**
 * Proxy controller between the Controller class and your end controllers to define app specific settings
 * This is just part of the example but might be useful in any app using authentication
 */
abstract class WatameloController extends Controller {
    protected $currentUser;
    protected $userLevels;
    // protected $actions = array(
    //     "" => array(
    //         "secureNeeded" => false,
    //         "level" => $this->userLevels['visitor'],
    //         "responseType" => RESPONSE_HTML
    //     )
    // );

    public function __construct(\Watamelo\App\Application $app) {
        parent::__construct($app);
        $this->currentUser = $this->app()->user();
        $this->userLevels = $this->app->userLevels();
    }

    /**
     * Return the user level required for the current action
     * Based on the $userLevels array defined in application, from database
     * @return integer the minimum user level required for this action
     */
    public function userLevelNeeded() {
        if (isset($this->actions[$this->action]) && isset($this->actions[$this->action]['level'])) {
            $level = $this->actions[$this->action]['level'];
        } else {
            $level = $this->userLevels['visitor'];
        }
        return $level;
    }

    /**
     * Check wether the current action needs a secure authentication
     * @return boolean true if action needs a secure authentication
     *                 false by default
     */
    public function secureNeeded() {
        if (isset($this->actions[$this->action]) && isset($this->actions[$this->action]['secureNeeded'])) {
            $secureNeeded = $this->actions[$this->action]['secureNeeded'];
        } else {
            $secureNeeded = false;
        }

        return $secureNeeded;
    }
}
