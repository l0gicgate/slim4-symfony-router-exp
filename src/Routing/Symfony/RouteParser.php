<?php

declare(strict_types=1);

namespace Slim\Routing\Symfony;

use Slim\Routing\RouteParser as SlimRouteParser;
use Slim\Interfaces\RouteCollectorInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteParser extends SlimRouteParser
{
    private RouteCollectorInterface $routeCollector;

    public function __construct(RouteCollectorInterface $routeCollector)
    {
        $this->routeCollector = $routeCollector;
    }

    protected function getRouteCollection(): RouteCollection
    {
        $routes = new RouteCollection();

        foreach ($this->routeCollector->getRoutes() as $route) {
            $symfonyRoute = (new Route($route->getPattern()))->setMethods($route->getMethods());
            $routes->add($route->getIdentifier(), $symfonyRoute);
        }

        return $routes;
    }

    public function relativeUrlFor(string $routeName, array $data = [], array $queryParams = []): string
    {
        $requestContext = new RequestContext();
        $routes = $this->getRouteCollection();

        $generator = new UrlGenerator($routes, $requestContext);
        $url = $generator->generate($routeName, $data);

        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }
}