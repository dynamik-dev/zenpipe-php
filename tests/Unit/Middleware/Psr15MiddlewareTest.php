<?php

use DynamikDev\ZenPipe\Middleware\CallbackRequestHandler;
use DynamikDev\ZenPipe\Middleware\PipelineMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

function createHandler(ResponseInterface $response): RequestHandlerInterface
{
    return new CallbackRequestHandler(fn () => $response);
}

function createRequest(string $method = 'GET', string $uri = '/'): ServerRequestInterface
{
    return new ServerRequest($method, $uri);
}

// =============================================================================
// CallbackRequestHandler Tests
// =============================================================================

test('callback request handler invokes callback with request', function () {
    $called = false;
    $receivedRequest = null;

    $handler = new CallbackRequestHandler(function ($request) use (&$called, &$receivedRequest) {
        $called = true;
        $receivedRequest = $request;

        return new Response(200);
    });

    $request = createRequest();
    $handler->handle($request);

    expect($called)->toBeTrue();
    expect($receivedRequest)->toBe($request);
});

test('callback request handler returns response from callback', function () {
    $expectedResponse = new Response(201, [], 'Created');

    $handler = new CallbackRequestHandler(fn () => $expectedResponse);

    $response = $handler->handle(createRequest());

    expect($response)->toBe($expectedResponse);
});

// =============================================================================
// PSR-15 Middleware in ZenPipe Pipeline Tests
// =============================================================================

test('pipeline auto-detects PSR-15 middleware in pipe()', function () {
    $middleware = new class () implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
        {
            $request = $request->withAttribute('middleware_called', true);

            return $handler->handle($request);
        }
    };

    $attributeValue = null;

    $result = zenpipe(createRequest())
        ->pipe($middleware)
        ->pipe(function ($request, $next, $return) use (&$attributeValue) {
            $attributeValue = $request->getAttribute('middleware_called');

            return $return(new Response(200));
        })
        ->process();

    expect($attributeValue)->toBeTrue();
    expect($result)->toBeInstanceOf(ResponseInterface::class);
});

test('PSR-15 middleware can return response directly without calling handler', function () {
    $middleware = new class () implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
        {
            return new Response(401, [], 'Unauthorized');
        }
    };

    $response = zenpipe(createRequest())
        ->pipe($middleware)
        ->pipe(function ($request, $next) {
            return $next($request->withAttribute('should_not_reach', true));
        })
        ->process();

    expect($response)->toBeInstanceOf(ResponseInterface::class);
    expect($response->getStatusCode())->toBe(401);
});

test('multiple PSR-15 middleware can be chained in pipeline', function () {
    $middleware1 = new class () implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
        {
            $request = $request->withAttribute('step', 1);

            return $handler->handle($request);
        }
    };

    $middleware2 = new class () implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
        {
            $step = $request->getAttribute('step');
            $request = $request->withAttribute('step', $step + 1);

            return $handler->handle($request);
        }
    };

    $finalStep = null;

    $response = zenpipe(createRequest())
        ->pipe($middleware1)
        ->pipe($middleware2)
        ->pipe(function ($request, $next, $return) use (&$finalStep) {
            $finalStep = $request->getAttribute('step');

            return $return(new Response(200));
        })
        ->process();

    expect($finalStep)->toBe(2);
    expect($response)->toBeInstanceOf(ResponseInterface::class);
});

test('PSR-15 middleware can be mixed with regular operators', function () {
    $middleware = new class () implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
        {
            $request = $request->withAttribute('from_middleware', true);

            return $handler->handle($request);
        }
    };

    $fromMiddleware = null;
    $fromOperator = null;

    $response = zenpipe(createRequest())
        ->pipe(function ($request, $next) {
            return $next($request->withAttribute('from_operator_1', true));
        })
        ->pipe($middleware)
        ->pipe(function ($request, $next, $return) use (&$fromMiddleware, &$fromOperator) {
            $fromMiddleware = $request->getAttribute('from_middleware');
            $fromOperator = $request->getAttribute('from_operator_1');

            return $return(new Response(200));
        })
        ->process();

    expect($fromMiddleware)->toBeTrue();
    expect($fromOperator)->toBeTrue();
    expect($response)->toBeInstanceOf(ResponseInterface::class);
});

test('PSR-15 middleware can modify response on way back', function () {
    $middleware = new class () implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
        {
            $response = $handler->handle($request);

            return $response->withHeader('X-Modified-By', 'Middleware');
        }
    };

    $response = zenpipe(createRequest())
        ->pipe($middleware)
        ->pipe(function ($request, $next, $return) {
            return $return(new Response(200, [], 'Original'));
        })
        ->process();

    expect($response->getHeaderLine('X-Modified-By'))->toBe('Middleware');
    expect((string) $response->getBody())->toBe('Original');
});

// =============================================================================
// ZenPipe as PSR-15 Middleware Tests (asMiddleware)
// =============================================================================

test('asMiddleware returns MiddlewareInterface', function () {
    $pipeline = zenpipe()
        ->pipe(fn ($req, $next) => $next($req));

    $middleware = $pipeline->asMiddleware();

    expect($middleware)->toBeInstanceOf(MiddlewareInterface::class);
    expect($middleware)->toBeInstanceOf(PipelineMiddleware::class);
});

test('pipeline middleware returns response when pipeline returns ResponseInterface', function () {
    $pipeline = zenpipe()
        ->pipe(function ($request, $next, $return) {
            return $return(new Response(403, [], 'Forbidden'));
        });

    $middleware = $pipeline->asMiddleware();

    $finalHandlerCalled = false;
    $handler = new CallbackRequestHandler(function () use (&$finalHandlerCalled) {
        $finalHandlerCalled = true;

        return new Response(200);
    });

    $response = $middleware->process(createRequest(), $handler);

    expect($response->getStatusCode())->toBe(403);
    expect($finalHandlerCalled)->toBeFalse();
});

test('pipeline middleware passes transformed request to handler when pipeline returns request', function () {
    $pipeline = zenpipe()
        ->pipe(function ($request, $next) {
            return $next($request->withAttribute('transformed', true));
        });

    $middleware = $pipeline->asMiddleware();

    $receivedRequest = null;
    $handler = new CallbackRequestHandler(function ($request) use (&$receivedRequest) {
        $receivedRequest = $request;

        return new Response(200);
    });

    $middleware->process(createRequest(), $handler);

    expect($receivedRequest)->not->toBeNull();
    expect($receivedRequest->getAttribute('transformed'))->toBeTrue();
});

test('pipeline middleware makes handler available via context', function () {
    $handlerFromContext = null;

    $pipeline = zenpipe()
        ->pipe(function ($request, $next, $return, $context) use (&$handlerFromContext) {
            $handlerFromContext = $context->handler ?? null;

            return $next($request);
        });

    $middleware = $pipeline->asMiddleware();

    $handler = new CallbackRequestHandler(fn () => new Response(200));
    $middleware->process(createRequest(), $handler);

    expect($handlerFromContext)->toBe($handler);
});

test('pipeline middleware allows explicit delegation via context handler', function () {
    $pipeline = zenpipe()
        ->pipe(function ($request, $next, $return, $context) {
            return $context->handler->handle($request->withAttribute('delegated', true));
        });

    $middleware = $pipeline->asMiddleware();

    $receivedRequest = null;
    $handler = new CallbackRequestHandler(function ($request) use (&$receivedRequest) {
        $receivedRequest = $request;

        return new Response(200);
    });

    $middleware->process(createRequest(), $handler);

    expect($receivedRequest->getAttribute('delegated'))->toBeTrue();
});

test('pipeline middleware works with exception handling', function () {
    $pipeline = zenpipe()
        ->pipe(function ($request, $next) {
            throw new RuntimeException('Pipeline error');
        })
        ->catch(function ($e, $value, $context) {
            return new Response(500, [], 'Error: '.$e->getMessage());
        });

    $middleware = $pipeline->asMiddleware();
    $handler = new CallbackRequestHandler(fn () => new Response(200));

    $response = $middleware->process(createRequest(), $handler);

    expect($response->getStatusCode())->toBe(500);
    expect((string) $response->getBody())->toBe('Error: Pipeline error');
});

test('pipeline middleware works with early returns', function () {
    $pipeline = zenpipe()
        ->pipe(function ($request, $next, $return) {
            if (! $request->hasHeader('Authorization')) {
                return $return(new Response(401, [], 'Unauthorized'));
            }

            return $next($request);
        })
        ->pipe(function ($request, $next) {
            return $next($request->withAttribute('authorized', true));
        });

    $middleware = $pipeline->asMiddleware();
    $handler = new CallbackRequestHandler(fn () => new Response(200));

    // Without auth header - should return 401
    $response = $middleware->process(createRequest(), $handler);
    expect($response->getStatusCode())->toBe(401);

    // With auth header - should pass through
    $authRequest = createRequest()->withHeader('Authorization', 'Bearer token');
    $response = $middleware->process($authRequest, $handler);
    expect($response->getStatusCode())->toBe(200);
});

// =============================================================================
// Integration Tests - Bidirectional
// =============================================================================

test('PSR-15 middleware wrapped in ZenPipe wrapped as PSR-15 middleware', function () {
    $innerMiddleware = new class () implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
        {
            $request = $request->withAttribute('inner', true);

            return $handler->handle($request);
        }
    };

    $receivedRequest = null;

    $pipeline = zenpipe()
        ->pipe($innerMiddleware)
        ->pipe(function ($request, $next, $return, $context) use (&$receivedRequest) {
            $receivedRequest = $request->withAttribute('outer', true);

            return $context->handler->handle($receivedRequest);
        });

    $outerMiddleware = $pipeline->asMiddleware();

    $finalHandlerRequest = null;
    $handler = new CallbackRequestHandler(function ($request) use (&$finalHandlerRequest) {
        $finalHandlerRequest = $request;

        return new Response(200);
    });

    $outerMiddleware->process(createRequest(), $handler);

    expect($finalHandlerRequest->getAttribute('inner'))->toBeTrue();
    expect($finalHandlerRequest->getAttribute('outer'))->toBeTrue();
});
