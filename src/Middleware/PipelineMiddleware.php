<?php

namespace DynamikDev\ZenPipe\Middleware;

use DynamikDev\ZenPipe\ZenPipe;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Wraps a ZenPipe pipeline as PSR-15 middleware.
 */
class PipelineMiddleware implements MiddlewareInterface
{
    /**
     * @param ZenPipe<ServerRequestInterface|ResponseInterface, object> $pipeline
     */
    public function __construct(private ZenPipe $pipeline)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Expose handler via context so pipeline operations can delegate explicitly
        $this->pipeline->withContext((object) ['handler' => $handler]);

        $result = $this->pipeline->process($request);

        if ($result instanceof ResponseInterface) {
            return $result;
        }

        return $handler->handle($result);
    }
}
