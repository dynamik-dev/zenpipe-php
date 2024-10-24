<?php

use DynamikDev\ZenPipe\ZenPipe;

if (! function_exists('zenpipe')) {
    /**
     * Create a new ZenPipe instance.
     *
     * @template T
     * @return ZenPipe<T>
     */
    function zenpipe(mixed $initialValue = null): ZenPipe
    {
        return new ZenPipe($initialValue);
    }
}
