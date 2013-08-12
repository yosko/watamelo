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
        if($type == 'atom') {
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

    /**
     * Return a data file (CSV)
     */
    public function executeExport() {

        $this->app()->view()->setParam( "users", $users );
        $this->app()->view()->renderView( "home" );
    }
}

?>