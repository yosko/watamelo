<?php

namespace Watamelo\Framework\Http\Router;

use LogicException;

class Route
{
    public ?string $url;
    public string $urlRegexp;
    public string|object $handler;
    public string $action;
    public string $method;
    public array $requiredParams;
    public array $foundParams;
    public array $additionalParams;
    public array $optionalParams;
    private array $middlewares;

    public function __construct(string $method, ?string $url, string|object $handler, string $action = 'index', $optional = [], $additional = [])
    {
        $this->method = $method;
        $this->url = $url;
        list($this->urlRegexp, $this->requiredParams) = $this->getUrlRegex();
        $this->handler = $handler;
        $this->action = $action;
        $this->foundParams = [];
        $this->additionalParams = [];
        $this->optionalParams = [];
        $this->middlewares = [];
    }

    public function addOptionalParam($name): Route
    {
        $this->optionalParams[$name] = null;
        return $this;
    }

    public function setAdditionalParam($name, $value): Route
    {
        $this->additionalParams[$name] = $value;
        return $this;
    }

    private function getUrlRegex(): array
    {
        // when url is null, match any URL
        if ($this->url === null) {
            return ['.*', []];
        }

        //handle parameter types
        $requiredParams = [];
        $regexp = preg_replace_callback(
            "/{(\w+)}/",
            function ($matches) use (&$requiredParams) {
                $requiredParams[] = $matches[1];
                // return '(.+)';
                return '([^/]+)';
            },
            str_replace('.', '\.', $this->url)
        );

        return [$regexp, $requiredParams];
    }

    public function requiredParamsTypes(): array
    {
        $types = [];
        $r = new \ReflectionMethod($this->handler, $this->action);
        $params = $r->getParameters();
        foreach ($params as $param) {
            $type = $param->getType()->getName();
            if (!in_array($type, ['int', 'string', 'float', 'bool', 'enum'])) {
                throw new LogicException(sprintf('Unsupported route param type "%s" for route %s Ms', $type, $this->method, $this->url));
            }
            $types[$param->getName()] = $type;
        }

        return $types;
    }

    public function middleware(...$middlewares)
    {
        $this->middlewares = array_unique(array_merge($this->middlewares, $middlewares));
    }
}
