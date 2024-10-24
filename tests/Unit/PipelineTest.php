<?php

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
