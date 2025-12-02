<?php

use DynamikDev\ZenPipe\ZenPipe;

test('pipeline processes multiple operations correctly', function () {
    $pipeline = zenpipe()
        ->pipe(function ($value, $next) {
            return $next($value + 1);
        })
        ->pipe(function ($value, $next) {
            return $next($value * 2);
        });

    expect($pipeline(5))->toBe(12);
});

test('pipeline can short-circuit', function () {
    $pipeline = zenpipe()
        ->pipe(function ($value, $next) {
            if ($value > 10) {
                return 'Too high';
            }

            return $next($value + 1);
        })
        ->pipe(function ($value, $next) {
            return $next($value * 2);
        })
        ->pipe(function ($value, $next) {
            return $next($value + 3);
        });

    expect($pipeline(5))->toBe(15);
    expect($pipeline(11))->toBe('Too high');
});

test('pipeline can use class methods as handlers', function () {
    $testClass = new class () {
        public function handle($value, $next)
        {
            $value *= 2;

            return $next($value);
        }
    };

    /**
     * @var ZenPipe<int>
     */
    $pipeline = zenpipe()
        ->pipe(function ($value, $next) {
            return $next($value + 1);
        })
        ->pipe([$testClass, 'handle'])
        ->pipe(function ($value, $next) {
            return $next($value + 3);
        });

    expect($pipeline(5))->toBe(15);  // (5 + 1) * 2 + 3 = 15
});

test('pipeline supports immediate processing', function () {
    $result = zenpipe(100)
        ->pipe(fn ($price, $next) => $next($price + 1))
        ->pipe(fn ($price, $next) => $next($price + 2))
        ->process();

    expect($result)->toBe(103);
});

test('pipeline supports both reusable and immediate processing', function () {
    // Reusable pipeline
    $calculator = zenpipe()
        ->pipe(fn ($price, $next) => $next($price + 1))
        ->pipe(fn ($price, $next) => $next($price + 2));

    expect($calculator(100))->toBe(103);

    // Immediate processing
    $result = zenpipe(10)
        ->pipe(fn ($price, $next) => $next($price + 1))
        ->pipe(fn ($price, $next) => $next($price + 2))
        ->process();

    expect($result)->toBe(13);
});

test('pipeline throws exception when no initial value is provided', function () {
    $pipeline = zenpipe()
        ->pipe(fn ($value, $next) => $next($value * 2));

    expect(fn () => $pipeline->process())->toThrow(InvalidArgumentException::class);
});

test('pipeline supports array of operations', function () {
    $pipeline = zenpipe()
        ->pipe([
            fn ($value, $next) => $next($value + 1),
            fn ($value, $next) => $next($value * 2),
            fn ($value, $next) => $next($value - 3),
        ]);

    expect($pipeline(5))->toBe(9);
});

test('pipeline supports early return using third parameter', function () {
    $pipeline = zenpipe()
        ->pipe(function ($value, $next, $return) {
            if ($value > 10) {
                return $return('early exit');
            }
            return $next($value + 1);
        })
        ->pipe(function ($value, $next) {
            return $next($value * 2);
        });

    expect($pipeline(5))->toBe(12);      // Normal flow: (5 + 1) * 2
    expect($pipeline(11))->toBe('early exit');  // Early return
});

test('pipeline supports early return in class methods', function () {
    $testClass = new class () {
        public function handle($value, $next, $return)
        {
            if ($value === 5) {
                return $return('caught five');
            }
            return $next($value * 2);
        }
    };

    $pipeline = zenpipe()
        ->pipe([$testClass, 'handle'])
        ->pipe(function ($value, $next) {
            return $next($value + 3);
        });

    expect($pipeline(5))->toBe('caught five');  // Early return
    expect($pipeline(3))->toBe(9);             // Normal flow: (3 * 2) + 3
});

test('pipeline early return works with array of operations', function () {
    $pipeline = zenpipe()
        ->pipe([
            fn ($value, $next) => $next($value + 1),
            fn ($value, $next, $return) => $value === 6 ? $return('found six') : $next($value * 2),
            fn ($value, $next) => $next($value - 3),
        ]);

    expect($pipeline(5))->toBe('found six');    // Early return when value becomes 6
    expect($pipeline(3))->toBe(5);             // Normal flow: ((3 + 1) * 2) - 3
});

test('pipeline supports context passing', function () {
    $context = new stdClass();
    $context->multiplier = 3;

    $result = zenpipe(10)
        ->withContext($context)
        ->pipe(fn ($value, $next, $return, $ctx) => $next($value * $ctx->multiplier))
        ->pipe(fn ($value, $next, $return, $ctx) => $next($value + $ctx->multiplier))
        ->process();

    expect($result)->toBe(33); // (10 * 3) + 3
});

test('pipeline context works with array context', function () {
    $context = ['prefix' => 'Hello', 'suffix' => '!'];

    $result = zenpipe('World')
        ->withContext($context)
        ->pipe(fn ($value, $next, $return, $ctx) => $next($ctx['prefix'] . ' ' . $value))
        ->pipe(fn ($value, $next, $return, $ctx) => $next($value . $ctx['suffix']))
        ->process();

    expect($result)->toBe('Hello World!');
});

test('pipeline context works with class methods', function () {
    $testClass = new class () {
        public function handle($value, $next, $return, $context)
        {
            return $next($value * $context->factor);
        }
    };

    $context = new stdClass();
    $context->factor = 5;

    $result = zenpipe(4)
        ->withContext($context)
        ->pipe([$testClass, 'handle'])
        ->pipe(fn ($value, $next) => $next($value + 1))
        ->process();

    expect($result)->toBe(21); // (4 * 5) + 1
});

test('pipeline context can be modified during execution', function () {
    $context = new stdClass();
    $context->steps = [];

    $result = zenpipe(1)
        ->withContext($context)
        ->pipe(function ($value, $next, $return, $ctx) {
            $ctx->steps[] = 'step1';
            return $next($value + 1);
        })
        ->pipe(function ($value, $next, $return, $ctx) {
            $ctx->steps[] = 'step2';
            return $next($value + 1);
        })
        ->process();

    expect($result)->toBe(3);
    expect($context->steps)->toBe(['step1', 'step2']);
});

test('pipeline context is null when not set', function () {
    $capturedContext = 'not null';

    zenpipe(5)
        ->pipe(function ($value, $next, $return, $context) use (&$capturedContext) {
            $capturedContext = $context;
            return $next($value);
        })
        ->process();

    expect($capturedContext)->toBeNull();
});

test('pipeline context works with custom DTO class', function () {
    $dto = new class ('test-user', ['admin', 'editor']) {
        public function __construct(
            public string $userId,
            public array $roles
        ) {
        }

        public function hasRole(string $role): bool
        {
            return in_array($role, $this->roles);
        }
    };

    $result = zenpipe(['action' => 'edit'])
        ->withContext($dto)
        ->pipe(function ($value, $next, $return, $ctx) {
            if (!$ctx->hasRole('editor')) {
                return $return(['error' => 'Unauthorized']);
            }
            return $next($value);
        })
        ->pipe(function ($value, $next, $return, $ctx) {
            $value['user'] = $ctx->userId;
            return $next($value);
        })
        ->process();

    expect($result)->toBe(['action' => 'edit', 'user' => 'test-user']);
});

test('pipeline catch handles exceptions with fallback value', function () {
    $result = zenpipe(5)
        ->pipe(fn ($value, $next) => $next($value * 2))
        ->pipe(function ($value, $next) {
            throw new RuntimeException('Something went wrong');
        })
        ->pipe(fn ($value, $next) => $next($value + 1))
        ->catch(fn ($e, $value) => 'fallback')
        ->process();

    expect($result)->toBe('fallback');
});

test('pipeline catch receives exception and original value', function () {
    $capturedException = null;
    $capturedValue = null;

    $result = zenpipe(42)
        ->pipe(function ($value, $next) {
            throw new InvalidArgumentException('Test error');
        })
        ->catch(function ($e, $value) use (&$capturedException, &$capturedValue) {
            $capturedException = $e;
            $capturedValue = $value;

            return 'handled';
        })
        ->process();

    expect($result)->toBe('handled');
    expect($capturedException)->toBeInstanceOf(InvalidArgumentException::class);
    expect($capturedException->getMessage())->toBe('Test error');
    expect($capturedValue)->toBe(42);
});

test('pipeline rethrows exception when no catch handler', function () {
    $pipeline = zenpipe(5)
        ->pipe(function ($value, $next) {
            throw new RuntimeException('Unhandled error');
        });

    expect(fn () => $pipeline->process())->toThrow(RuntimeException::class, 'Unhandled error');
});

test('pipeline catch works with class method operations', function () {
    $testClass = new class () {
        public function handle($value, $next)
        {
            throw new RuntimeException('Class method error');
        }
    };

    $result = zenpipe(10)
        ->pipe([$testClass, 'handle'])
        ->catch(fn ($e, $value) => $value * 2)
        ->process();

    expect($result)->toBe(20);
});

test('pipeline catch can return computed value based on exception', function () {
    $result = zenpipe(['items' => [1, 2, 3]])
        ->pipe(function ($value, $next) {
            throw new RuntimeException('Processing failed');
        })
        ->catch(fn ($e, $value) => [
            'error' => $e->getMessage(),
            'items_count' => count($value['items']),
        ])
        ->process();

    expect($result)->toBe([
        'error' => 'Processing failed',
        'items_count' => 3,
    ]);
});

test('pipeline catch handler receives context', function () {
    $context = new stdClass();
    $context->fallbackMessage = 'Operation failed gracefully';

    $result = zenpipe(10)
        ->withContext($context)
        ->pipe(function ($value, $next) {
            throw new RuntimeException('Error occurred');
        })
        ->catch(fn ($e, $value, $ctx) => [
            'value' => $value,
            'message' => $ctx->fallbackMessage,
        ])
        ->process();

    expect($result)->toBe([
        'value' => 10,
        'message' => 'Operation failed gracefully',
    ]);
});
