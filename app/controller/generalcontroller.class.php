<?php

namespace Watamelo\Controllers;

/**
 * Controller for displaying global pages
 */
class GeneralController extends WatameloController {
    protected $currentUser;

    //define specific behavior for some actions (here for action "executeAdmin")
    protected $actions;

    public function __construct(\Watamelo\App\Application $app) {
        parent::__construct($app);
        $this->currentUser = $this->app()->user();
        $this->actions = array(
            "admin" => array(
                "secureNeeded" => true,
                "level" => $this->userLevels['admin']
            )
        );
    }

    /**
     * Show application's homepage
     */
    public function executeIndex() {
        $userManager = $this->app()->getManagerOf('user');

        $users = $userManager->getList();

        $this->app()->view()->setParam( "users", $users );
        $this->app()->view()->renderView( "home" );
    }

    /**
     * Show an admin page (with secure access)
     */
    public function executeAdmin() {
        $this->app()->view()->renderView( "admin" );
    }

    /**
     * Return a data file (CSV/JSON)
     */
    public function executeExport() {
        $data = array();

        if($this->parameters['type'] == 'csv') {
            //the data array must be bidimensional and every item must have the same form:
            $data = array(
                array(
                    'column1' => 1,
                    'column2' => 'text "1"'
                ),
                array(
                    'column1' => 2,
                    'column2' => 'text "2"'
                )
            );
            $this->app()->view()->renderData( $data, RESPONSE_CSV );
        } else {
            //the $data array can have any number of dimension
            $data = $this->app()->config()->getAll();
            $this->app()->view()->renderData( $data, RESPONSE_JSON, array('fileName' => 'test.json') );

            //Note: to use json data in an ajax request, don't use the "fileName" parameter
        }
    }

    /**
     * Show an RSS/Atom feed
     */
    public function executeFeed() {
        $data = array();
        $type = $this->parameters['type'];

        if(isset($_GET['url'])) {
            $self = $this->app()->view()->baseUrl().$_GET['url'];
        } else {
            $self = $this->app()->view()->baseUrl().$_GET['p'];
        }

        $data['title'] = 'Watamelo useless feed';
        $data['link'] = $this->app()->view()->rootUrl();
        $data['subtitle'] = 'A feed of random, useless data';
        $data['self'] = $self;
        $data['copyright'] = 'Yosko';
        $data['language'] = 'en-en';

        //atom feed
        if($type == RESPONSE_ATOM) {
            $data['update'] = date(DATE_ATOM); //watch out for the date format!

        //rss feed
        } else {
            //nothing here, yet
        }

        for($i = 5; $i > 0; $i--) {
            $item = array();

            $item['link'] = $this->app()->view()->baseUrl().$i;
            $item['title'] = 'Item #'.$i;
            $item['summary'] = 'Content of my feed item #'.$i;

            //atom feed
            if($type == 'atom') {
                $item['update'] = date(DATE_ATOM); //watch out for the date format!
                $item['author'] = array(
                    'name' => 'bob',
                    'email' => 'bob@example.com'
                );

            //rss feed
            } else {
                $guid = number_format(hexdec(md5($item['link'])), 0, '.', '');
                $item['guid'] = $guid;
                $item['pubDate'] = date(DATE_RSS);  //watch out for the date format!
            }

            $data['items'][] = $item;
        }

        $this->app()->view()->renderFeed( $data, $type );
    }
}
