<?php

declare(strict_types=1);

namespace Slim\Routing\Symfony;

use Psr\Http\Message\UriFactoryInterface;
use Slim\Interfaces\DispatcherInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Psr7\Factory\UriFactory;
use Slim\Routing\RoutingResults;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Dispatcher implements DispatcherInterface
{
    private array $allowedMethodsByUri = [];
    private RouteCollectorInterface $routeCollector;
    private UriFactoryInterface $uriFactory;

    public function __construct(RouteCollectorInterface $routeCollector, UriFactory $uriFactory)
    {
        $this->routeCollector = $routeCollector;
        $this->uriFactory = $uriFactory;
    }

    public function dispatch(string $method, string $uri): RoutingResults
    {
        $routes = new RouteCollection();

        foreach ($this->routeCollector->getRoutes() as $route) {
            $this->allowedMethodsByUri[$route->getPattern()] = $route->getMethods();
            $symfonyRoute = (new Route($route->getPattern()))->setMethods($route->getMethods());
            $routes->add($route->getIdentifier(), $symfonyRoute);
        }

        $parsedUri = $this->uriFactory->createUri($uri);
        $requestContext = new RequestContext(
            '',
            $method,
            $parsedUri->getHost(),
            $parsedUri->getScheme(),
            $parsedUri->getPort() ?? 80,
            $parsedUri->getPort() ?? 443,
            $parsedUri->getPath(),
            $parsedUri->getQuery(),
        );

        $matcher = new UrlMatcher($routes, $requestContext);

        try {
            $match = $matcher->match($uri);
            $identifier = '';
            $arguments = [];

            foreach ($match as $key => $value) {
                switch ($key) {
                    case '_route':
                        $identifier = $value;
                        break;

                    case '_controller':
                        // Do Nothing
                        break;

                    default:
                        $arguments[$key] = $value;
                        break;
                }
            }

            return new RoutingResults($this, $method, $uri, RoutingResults::FOUND, $identifier, $arguments);
        } catch (ResourceNotFoundException $e) {
            return new RoutingResults($this, $method, $uri, RoutingResults::NOT_FOUND);
        } catch (MethodNotAllowedException $e) {
            $this->allowedMethodsByUri[$uri] = $e->getAllowedMethods();
            return new RoutingResults($this, $method, $uri, RoutingResults::METHOD_NOT_ALLOWED);
        }
    }

    public function getAllowedMethods(string $uri): array
    {
        return $this->allowedMethodsByUri[$uri] ?? [];
    }
}