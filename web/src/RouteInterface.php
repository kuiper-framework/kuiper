<?php

namespace kuiper\web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RouteInterface
{
    /**
     * Sets route name.
     *
     * @param string $name
     *
     * @return static
     *
     * @throws \InvalidArgumentException if the route name is not a string
     */
    public function name(string $name);

    /**
     * Return an instance with the specified attributes.
     *
     * - scheme
     * - host
     * - port
     * - prefix
     *
     * @param array $attributes
     *
     * @return static
     */
    public function match(array $attributes);

    /**
     * Gets the request methods.
     *
     * @return string[]
     */
    public function getMethods(): array;

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
    public function getPattern(): string;

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
     * Gets route name.
     *
     * @return null|string
     */
    public function getName();

    /**
     * Gets the attributes.
     *
     * @return array
     */
    public function getAttributes(): array;

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
    public function getArguments(): array;

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
    public function run(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
