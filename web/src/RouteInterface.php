<?php

namespace kuiper\web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RouteInterface
{
    /**
     * Constructs the route.
     *
     * @param string[]        $methods Numeric array of HTTP method names
     * @param string          $pattern The route URI pattern
     * @param callable|string $action  The route callback routine
     */
    public function __construct(array $methods, $pattern, $action);

    /**
     * Gets the request methods.
     *
     * @return string[]
     */
    public function getMethods();

    /**
     * Return an instance with the specified http request methods.
     *
     * @param array $methods
     *
     * @return static
     */
    public function withMethods(array $methods);

    /**
     * Gets route pattern.
     *
     * @return string
     */
    public function getPattern();

    /**
     * Gets route callback.
     *
     * @return callable
     */
    public function getAction();

    /**
     * Return an instance with the specified action.
     *
     * @param callable|string $action
     *
     * @return static
     */
    public function withAction($action);

    /**
     * Sets route name.
     *
     * @param string $name
     *
     * @return static
     *
     * @throws \InvalidArgumentException if the route name is not a string
     */
    public function name($name);

    /**
     * Gets route name.
     *
     * @return null|string
     */
    public function getName();

    /**
     * Return an instance with the specified attributes.
     *
     * - scheme
     * - host
     * - port
     * - prefix
     *
     * @param array $condition
     *
     * @return self
     */
    public function match(array $attributes);

    /**
     * Gets the attributes.
     *
     * @return array
     */
    public function getAttributes();

    /**
     * Replace route arguments.
     *
     * @param array $arguments
     *
     * @return static
     */
    public function withArguments(array $arguments);

    /**
     * Gets route arguments.
     *
     * @return array
     */
    public function getArguments();

    /**
     * Run route.
     *
     * This method traverses the middleware stack, including the route's callable
     * and captures the resultant HTTP response object. It then sends the response
     * back to the Application.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function run(ServerRequestInterface $request, ResponseInterface $response);
}
