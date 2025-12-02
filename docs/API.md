# ZenPipe API Reference

The `ZenPipe` class provides a fluent interface for building and executing pipelines of operations.

## Class Overview

```php
namespace DynamikDev\ZenPipe;

class ZenPipe
```

## Methods

### Constructor

```php
public function __construct(mixed $initialValue = null)
```

Creates a new pipeline instance.

- **Parameters:**
  - `$initialValue` (mixed|null): The initial value to be processed through the pipeline.

### make()

```php
public static function make(mixed $initialValue = null): self
```

Static factory method to create a new pipeline instance.

- **Parameters:**
  - `$initialValue` (mixed|null): The initial value to be processed through the pipeline.
- **Returns:** A new `ZenPipe` instance.

### withContext()

```php
public function withContext(mixed $context): self
```

Sets a context object that will be passed to all operations as the fourth parameter.

- **Parameters:**
  - `$context` (mixed): Any value to be passed as context (object, array, DTO, etc.)
- **Returns:** The `ZenPipe` instance for method chaining.

**Example:**
```php
$pipeline = zenpipe($value)
    ->withContext(new MyContext())
    ->pipe(fn($v, $next, $return, MyContext $ctx) => $next($v));
```

### catch()

```php
public function catch(callable $handler): self
```

Sets an exception handler for the pipeline.

- **Parameters:**
  - `$handler` (callable): A function that receives `(Throwable $e, mixed $originalValue, mixed $context)` and returns a fallback value.
- **Returns:** The `ZenPipe` instance for method chaining.

**Example:**
```php
$pipeline = zenpipe($value)
    ->withContext($myContext)
    ->pipe(fn($v, $next) => $next(riskyOperation($v)))
    ->catch(fn($e, $value, $ctx) => ['error' => $e->getMessage()]);
```

If an exception occurs and no catch handler is set, the exception propagates normally.

### pipe()

```php
public function pipe($operation): self
```

Adds an operation to the pipeline.

- **Parameters:**
  - `$operation`: Can be one of:
    - `callable`: A function to process the value
    - `array{class-string, string}`: A tuple of [className, methodName]
    - `array`: An array of operations to be added sequentially
    - `MiddlewareInterface`: A PSR-15 middleware (auto-detected)
- **Returns:** The `ZenPipe` instance for method chaining.
- **Throws:** `\InvalidArgumentException` if the specified class does not exist.

**Operation Parameters:**
Operations receive up to four parameters:
1. `$value` - The current value being processed
2. `$next` - Callback to pass value to next operation
3. `$return` - Callback to exit pipeline early with a value
4. `$context` - The context set via `withContext()` (null if not set)

### process()

```php
public function process($initialValue = null)
```

Executes the pipeline with the given initial value.

- **Parameters:**
  - `$initialValue` (mixed|null): The value to process. If not provided, uses the value from constructor.
- **Returns:** The processed value after running through all operations.
- **Throws:** `\InvalidArgumentException` if no initial value is provided.

### __invoke()

```php
public function __invoke($initialValue)
```

Makes the pipeline instance callable.

- **Parameters:**
  - `$initialValue`: The value to process through the pipeline.
- **Returns:** The processed value after running through all operations.

### asMiddleware()

```php
public function asMiddleware(): MiddlewareInterface
```

Wraps the pipeline as a PSR-15 middleware.

- **Returns:** A `MiddlewareInterface` instance.

See [PSR-15 Middleware](#psr-15-middleware) for details.

---

## PSR-15 Middleware

ZenPipe provides bidirectional PSR-15 middleware support. Requires `psr/http-server-middleware`.

### Using PSR-15 Middleware in a Pipeline

Pass any `MiddlewareInterface` directly to `pipe()` - it's auto-detected:

```php
$response = zenpipe($request)
    ->pipe(new CorsMiddleware())
    ->pipe(new AuthMiddleware())
    ->pipe(fn($req, $next, $return) => $return(new Response(200)))
    ->process();
```

When using PSR-15 middleware, the pipeline must return a `ResponseInterface`.

### Using ZenPipe as PSR-15 Middleware

Wrap a pipeline with `asMiddleware()` for use in PSR-15 frameworks:

```php
$pipeline = zenpipe()
    ->pipe(fn($req, $next) => $next($req->withAttribute('processed', true)));

$app->middleware($pipeline->asMiddleware());
```

**Behavior:**
- If the pipeline returns a `ResponseInterface`, it's returned directly
- If the pipeline returns a `ServerRequestInterface`, it's passed to the next handler
- The PSR-15 handler is available via `$context->handler` for explicit delegation

```php
$authPipeline = zenpipe()
    ->pipe(function ($req, $next, $return, $ctx) {
        if (!$req->hasHeader('Authorization')) {
            return $return(new Response(401));
        }
        // Delegate to next PSR-15 handler
        return $ctx->handler->handle($req);
    });

$app->middleware($authPipeline->asMiddleware());
```
