<?php

namespace Yosko\Watamelo;

/**
 * Encapsulate HTTP request
 */
class HttpRequest
{
    private string $completeUrl;
    private string $baseUrl;
    private string $url;
    private array $headers;
    private array $urlParams;

    public function __construct(array $urlParams = [])
    {
        $this->completeUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // remove base path (to the app) from url
        $uri_chars = mb_str_split($this->completeUrl);
        $script_chars = mb_str_split($_SERVER['SCRIPT_NAME']);
        for ($i = 0; $i < count($script_chars); $i++) {
            if ($script_chars[$i] !== $uri_chars[$i]) {
                break;
            }
        }
        $this->baseUrl = implode('', array_slice($uri_chars, 0, $i - 1));
        $this->url = implode('', array_slice($uri_chars, $i - 1));

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
        return $_GET;
    }
}
