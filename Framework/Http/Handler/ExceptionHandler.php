<?php

namespace Watamelo\Framework\Http\Handler;

use Watamelo\Framework\View;

/**
 * Exception handler using the View system if possible.
 * Inspired by: https://stackoverflow.com/a/7939492/863323
 */
class ExceptionHandler {
    private $rethrow;
    private View $view;

    public function __construct()
    {
        set_exception_handler([$this, 'handler']);
    }

    public function setView($view) {
        $this->view = $view;
    }

    /**
     * Display exceptions and errors in a nicely manner if possible
     * @param Throwable $e
     * @throws Exception
     */
    public function handler($exception)
    {
        if (isset($this->view)) {
            $this->view->setParam('exception', $exception);
            // TODO: new path definition to be tested
            echo $this->view->render('exception', __DIR__.'/../../templates/');
        } else {
            $this->rethrow = $exception;
        }
    }

    public function __destruct()
    {
        if ($this->rethrow) {
            throw $this->rethrow;
        }
    }
}
