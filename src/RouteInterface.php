<?php
namespace kuiper\web;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

interface RouteInterface
{
    /**
     * Replace route arguments
     *
     * @param array $arguments
     *
     * @return static
     */
    public function setArguments(array $arguments);

    /**
     * Get route arguments
     *
     * @return array
     */
    public function getArguments();

    /**
     * Set route name
     *
     * @param string $name
     *
     * @return static
     * @throws \InvalidArgumentException if the route name is not a string
     */
    public function setName($name);

    /**
     * Get route name
     *
     * @return null|string
     */
    public function getName();

    /**
     * Gets route http request methods
     *
     * @param string[] $methods
     *
     * @return self
     */
    public function setMethods(array $methods);

    /**
     * @return string[]
     */
    public function getMethods();

    /**
     * Set route pattern
     *
     * @param string $pattern
     *
     * @return static
     * @throws \InvalidArgumentException if the route pattern is not a string
     */
    public function setPattern($pattern);

    /**
     * Get route pattern
     *
     * @return string
     */
    public function getPattern();

    /**
     * Get route callback
     *
     * @return callable
     */
    public function getHandler();

    /**
     * Run route
     *
     * This method traverses the middleware stack, including the route's callable
     * and captures the resultant HTTP response object. It then sends the response
     * back to the Application.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function run(ServerRequestInterface $request, ResponseInterface $response);
}
