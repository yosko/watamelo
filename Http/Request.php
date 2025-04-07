<?php

namespace Yosko\Watamelo\Http;

/**
 * Encapsulate HTTP request
 */
class Request
{
    private ?string $basePath = null;
    private ?string $path = null;
    private ?array $headers = null;
    private ?array $bodyParams = null;
    private ?string $rawBody = null;


    /******** Http method ********/

    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }


    /******** URL ********/

    public function getUri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Path to the application.
     * (useful if not installed at the server's document root)
     * TODO: to be tested
     *
     * @return string
     */
    public function getBasePath(): string
    {
        if ($this->basePath !== null)
            return $this->basePath;

        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        $length = min(mb_strlen($script), mb_strlen($uri));
        $common = '';

        for ($i = 0; $i < $length; $i++) {
            if (mb_substr($script, $i, 1) !== mb_substr($uri, $i, 1)) {
                break;
            }
            $common .= mb_substr($script, $i, 1);
        }

        $this->basePath = rtrim(dirname($common), '/');
        return $this->basePath;
    }

    /**
     * URL path within the app.
     * (without the base path *to* the app).
     * TODO: to be tested
     *
     * @return string
     */
    public function getPath(): string
    {
        if ($this->path !== null)
            return $this->path;

        $uriPath = parse_url($this->getUri(), PHP_URL_PATH);
        $base = $this->getBasePath();

        $path = mb_substr($uriPath, mb_strlen($base)) ?: '/';
        $this->path = '/' . ltrim($path, '/');

        return $this->path;
    }


    /******** URL parameters ********/

    public function getQueryString(): ?string
    {
        return $_SERVER['QUERY_STRING'] ?? null;
    }

    public function getQueryParams(): array
    {
        return $_GET;
    }


    /******** Headers ********/

    public function getHeaders(): array
    {
        return $this->headers ??= getallheaders();
    }

    public function getHeader(string $key): mixed
    {
        return $this->getHeaders()[$key] ?? null;
    }

    public function getContentType(): ?string
    {
        // or $this->getHeader('CONTENT_TYPE');
        return $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? null;
    }

    public function getHost()
    {
        // or $this->getHeader('Host');
        return $_SERVER['HTTP_HOST'];
    }


    /******** Body & body parameters ********/

    public function getRawBody(): string
    {
        return $this->rawBody ??= file_get_contents('php://input') ?: '';
    }

    public function getBodyParams(): array
    {
        if ($this->bodyParams !== null)
            return $this->bodyParams;

        $type = $this->getContentType();
        $body = $this->getRawBody();

        if (str_contains($type, 'application/json')) {
            $parsed = json_decode($body, true);
            $this->bodyParams = is_array($parsed) ? $parsed : [];
        } elseif (str_contains($type, 'application/x-www-form-urlencoded')) {
            parse_str($body, $parsed);
            $this->bodyParams = $parsed;
        } elseif (str_contains($type, 'multipart/form-data')) {
            $this->bodyParams = $_POST;
        } else {
            $this->bodyParams = [];
        }

        return $this->bodyParams;
    }

    public function getParam(string $key): mixed
    {
        return $this->getBodyParams()[$key] ?? $this->getQueryParams()[$key] ?? null;
    }

    // TODO: remove this after tests
    // public function __construct()
    // {
    //     $this->completeUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    //     // remove base path (to the app) from url
    //     $uri_chars = mb_str_split($this->completeUrl);
    //     $script_chars = mb_str_split($_SERVER['SCRIPT_NAME']);
    //     for ($i = 0; $i < count($script_chars); $i++) {
    //         if ($script_chars[$i] !== $uri_chars[$i]) {
    //             break;
    //         }
    //     }
    //     $this->baseUrl = implode('', array_slice($uri_chars, 0, $i - 1));
    //     $this->url = implode('', array_slice($uri_chars, $i - 1));
    // }
}
