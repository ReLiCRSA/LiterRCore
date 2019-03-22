<?php

namespace Framework;

/**
 * Class Router
 * @package Framework
 */
class Router
{
    /** @var RequestInterface  */
    private $request;

    /** @var array Allowed HTTP Verbs */
    private $allowedVerbs = [
        'POST',
        'GET'
    ];

    /** @var array Route Variables that we have found */
    private $routeVariables = [];

    /**
     * Router constructor.
     * @param RequestInterface $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Here we hande the input from the router file. web.php in most cases
     *
     * @param $name
     * @param $args
     */
    public function __call($name, $args)
    {
        list($route, $method) = $args;
        if (!in_array(strtoupper($name), $this->allowedVerbs)) {
            $this->invalidMethodHandler();
        }
        $this->{strtolower($name)}[$this->routeClean($route)] = $method;
    }

    /**
     * Sanitize the router so that we can get a clean compare for uri and listed routes
     *
     * @param $routeEndpoint
     * @return string
     */
    public function routeClean($routeEndpoint)
    {
        $result = rtrim($routeEndpoint, '/');
        if ($result === "") {
            $result = "/";
        }
        return $result;
    }

    /**
     * Set the header for invalid requests
     */
    private function invalidMethodHandler()
    {
        header("{$this->request->serverProtocol} 405 Method Not Allowed");
    }

    /**
     * Default return if we dont find anything
     */
    private function defaultRequestHandler()
    {
        header("{$this->request->serverProtocol} 404 Not Found");
    }

    /**
     * This resolves the request after the framework is up
     */
    public function resolve()
    {
        $methodDictionary = $this->{strtolower($this->request->requestMethod)};
        $formattedRoute = $this->routeClean($this->request->requestUri);
        $method = $this->routeSearch($methodDictionary, $formattedRoute);

        if (is_null($method)) {
            $this->defaultRequestHandler();
            return;
        }

        switch (gettype($method)) {
            case 'object':
                echo call_user_func_array($method, array($this->request));
                break;
            case 'string':
                $target = explode("@", $method);
                $controller = 'App\Controllers\\'.$target[0];
                $functionCall = new $controller;
                echo call_user_func_array([$functionCall,$target[1]], $this->routeVariables);
                break;
            default:
                $this->defaultRequestHandler();
        }
    }

    /**
     * Find the route we are accessing
     *
     * @param $methodDictionary
     * @param $formattedRoute
     * @return null
     */
    public function routeSearch($methodDictionary, $formattedRoute)
    {
        foreach ($methodDictionary as $checkRoute => $methodCall) {
            $regex = preg_replace_callback('/({\w+})/', function ($matches) {
                if (isset($matches[1], $this->filters[$matches[1]])) {
                    return $this->filters[$matches[1]];
                }
                return '([\w-%]+)';
            }, $checkRoute);
            $pattern = '@^' . $regex . '/?$@i';
            $return = preg_match_all($pattern, $formattedRoute, $matches);
            if ($return) {
                if (!empty($matches[1])) {
                    $this->routeVariables = $matches[1];
                }
                return $methodCall;
            }
        }
        return null;
    }

    /**
     * On destruct force the resolution of the request
     */
    public function __destruct()
    {
        $this->resolve();
    }
}
