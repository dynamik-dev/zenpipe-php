<?php

use DynamikDev\ZenPipe\ZenPipe;

if (! function_exists('zenpipe')) {
    /**
     * Create a new ZenPipe instance.
     *
     * @template T
     * @template TContext
     *
     * @return ZenPipe<T, TContext>
     */
    function zenpipe(mixed $initialValue = null): ZenPipe
    {
        return new ZenPipe($initialValue);
    }
}
