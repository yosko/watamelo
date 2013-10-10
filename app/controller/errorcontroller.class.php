<?php

/**
 * Controller for displaying general errors
 */
class ErrorController extends Controller {
    public function issetHTTPError() {
        if(isset($_SERVER['REDIRECT_STATUS'])) {
            switch ($_SERVER['REDIRECT_STATUS']) {
                case 400:
                case 403:
                case 404:
                case 405:
                case 408:
                case 500:
                case 502:
                case 504:
                    return $_SERVER['REDIRECT_STATUS'];
                    break;
                default:
                    return "index";
            }
        } else {
            return false;
        }
    }
    
    //default/unrecognized error
    public function executeIndex() {
        
    }
    
    //Bad Request
    public function execute400() {
        
    }
    
    //Forbidden
    public function execute403() {
        header("HTTP/1.0 403 Forbidden");
        $this->app()->view()->renderView( "error.403" );
    }
    
    //Not Found
    public function execute404() {
        header("HTTP/1.0 404 Not Found");
        $this->app()->view()->renderView( "error.404" );
    }
    
    //Method Not Allowed
    public function execute405() {
        
    }
    
    //Request Timeout
    public function execute408() {
        
    }
    
    //Internal Server Error
    public function execute500() {
        
    }
    
    //Bad Gateway
    public function execute502() {
        
    }
    
    //Gateway Timeout
    public function execute504() {
        
    }
}
?>