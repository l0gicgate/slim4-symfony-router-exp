<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\App;
use Slim\CallableResolver;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\ResponseEmitter;
use Slim\Routing\RouteCollector;
use Slim\Routing\RouteContext;
use Slim\Routing\RouteResolver;
use Slim\Routing\Symfony\Dispatcher;
use Slim\Routing\Symfony\RouteParser;

require __DIR__ . '/../vendor/autoload.php';

$container = (new ContainerBuilder())->build();
$callableResolver = new CallableResolver($container);
$responseFactory = new ResponseFactory();
$uriFactory = new UriFactory();

$routeCollector = new RouteCollector($responseFactory, $callableResolver, $container);
$routeParser = new RouteParser($routeCollector);
$dispatcher = new Dispatcher($routeCollector, $uriFactory);
$routeResolver = new RouteResolver($routeCollector, $dispatcher);

$app = new App(
    $responseFactory,
    $container,
    $callableResolver,
    $routeCollector,
    $routeResolver,
);

$app->get('/hello/{name}', function (Request $request, Response $response, $args) use ($responseFactory) {
    $routeContext = RouteContext::fromRequest($request);
    $anotherUrl = $routeContext->getRouteParser()->fullUrlFor($request->getUri(), 'hello', ['name' => 'Bill']);

    $response->getBody()->write("Hello {$args['name']}. ");
    return $response;
})->setName('hello');

$uri = $uriFactory->createUri('http://example.com/hello/john');
$headers = new Headers();
$body = (new StreamFactory())->createStream();
$request = new Request('GET', $uri, $headers, [], [], $body);

$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();
$responseEmitter->emit($response);
