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
