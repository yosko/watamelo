<?php

namespace Yosko\Watamelo;

/**
 * Encapsulate HTTP request
 */
class HttpRequest
{
    private string $url;
    private string $urlNameParam;
    private array $headers;
    private array $urlParams;

    public function __construct(?string $urlNameParam = null, array $urlParams = [])
    {
        if (is_null($urlNameParam)) {
            $this->url = str_replace(dirname($_SERVER['SCRIPT_NAME']), '', $_SERVER['REQUEST_URI']);
        } else {
            $this->urlNameParam = $urlNameParam;
            $this->url = $_GET[$urlNameParam] ?? '';
        }

        $this->urlParams = $urlParams;
    }

    public function headers()
    {
        if (!isset($this->headers))
            $this->headers = getallheaders();
        return $this->headers;
    }

    public function header($name)
    {
        // TODO: case-insensitive lookup
        return $this->headers()[$name] ?? null;
    }

    public function body()
    {
        return file_get_contents('php://input');
    }

    public function host()
    {
        // or self::header('Host');
        return $_SERVER['HTTP_HOST'];
    }

    // public function urlParamString()
    // {
    //     return $_SERVER['QUERY_STRING'];
    // }

    public function method(): HttpMethods
    {
        return HttpMethods::from($_SERVER['REQUEST_METHOD']);
    }

    public function url(): string
    {
        return $this->url;
    }

    public function urlParams(): array
    {
        return $this->urlParams;
    }

    public function urlParam($name): array
    {
        return $this->urlParams[$name] ?? null;
    }

    public function queryString(): array
    {
        $get = $_GET;
        if (isset($this->urlNameParam))
            unset($get[$this->urlNameParam]);
        return $get;
    }
}
