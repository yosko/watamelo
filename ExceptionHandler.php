<?php

namespace Watamelo;

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
            $this->view()->setParam('exception', exception);
            echo $this->view()->renderView('exception', false);
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
