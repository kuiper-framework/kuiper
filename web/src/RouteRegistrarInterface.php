<?php

namespace kuiper\web;

interface RouteRegistrarInterface
{
    /**
     * Register a new GET route with the router.
     *
     * @param string                $pattern
     * @param \Closure|array|string $action
     *
     * @return RouteInterface
     */
    public function get(string $pattern, $action);

    /**
     * Register a new POST route with the router.
     *
     * @param string                $pattern
     * @param \Closure|array|string $action
     *
     * @return RouteInterface
     */
    public function post(string $pattern, $action);

    /**
     * Register a new PUT route with the router.
     *
     * @param string                $pattern
     * @param \Closure|array|string $action
     *
     * @return RouteInterface
     */
    public function put(string $pattern, $action);

    /**
     * Register a new DELETE route with the router.
     *
     * @param string                $pattern
     * @param \Closure|array|string $action
     *
     * @return RouteInterface
     */
    public function delete(string $pattern, $action);

    /**
     * Register a new PATCH route with the router.
     *
     * @param string                $pattern
     * @param \Closure|array|string $action
     *
     * @return RouteInterface
     */
    public function patch(string $pattern, $action);

    /**
     * Register a new OPTIONS route with the router.
     *
     * @param string                $pattern
     * @param \Closure|array|string $action
     *
     * @return RouteInterface
     */
    public function options(string $pattern, $action);

    /**
     * Adds route for any HTTP method.
     *
     * @param string                $pattern
     * @param \Closure|array|string $action
     *
     * @return RouteInterface
     */
    public function any(string $pattern, $action);

    /**
     * Register a new route with the given verbs.
     *
     * @param array|string          $methods
     * @param string                $pattern
     * @param \Closure|array|string $action
     *
     * @return RouteInterface
     */
    public function map(array $methods, string $pattern, $action);

    /**
     * Create a route group with shared attributes.
     *
     * @param array    $attributes
     * @param \Closure $callback
     */
    public function group(array $attributes, \Closure $callback);

    /**
     * Gets all routes.
     *
     * @return RouteInterface[]
     */
    public function getRoutes() : array ;
}
