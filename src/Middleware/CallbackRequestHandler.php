<?php

namespace DynamikDev\ZenPipe\Middleware;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Bridges ZenPipe's $next callback to PSR-15's RequestHandlerInterface.
 */
class CallbackRequestHandler implements RequestHandlerInterface
{
    public function __construct(private Closure $callback)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return ($this->callback)($request);
    }
}
