<?php

/**
 * Controller for displaying global pages
 */
class GeneralController extends Controller {
    protected $currentUser;

    public function __construct(Application $app) {
        parent::__construct($app);
        $this->currentUser = $this->app()->user();
    }

    /**
     * Show application's homepage
     */
    public function executeIndex() {
        $userManager = $this->app()->managers()->getManagerOf('user');
        
        $users = $userManager->getList();

        $this->app()->view()->setParam( "users", $users );
        $this->app()->view()->renderView( "home" );
    }
}

?>