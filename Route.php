<?php

namespace Yosko\Watamelo;

class Route
{
    public string $url;
    public string $controller;
    public string $action;
    public string $method;
    public array $requestParams;
    public array $additionalParams;
    public array $optionalParams;

    public function __construct(string $url, string $controller, string $action = 'index',
                                string $method = 'GET')
    {
        $this->url = $url;
        $this->controller = $controller;
        $this->action = $action;
        $this->method = $method;
        $this->requestParams = [];
        $this->additionalParams = [];
        $this->optionalParams = [];
    }

    public function addOptionalParam($name)
    {
        $this->optionalParams[$name] = null;
        return $this;
    }

    public function setAdditionalParam($name, $value)
    {
        $this->additionalParams[$name] = $value;
        return $this;
    }
}