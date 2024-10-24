<?php

use DynamikDev\ZenPipe\ZenPipe;

if (! function_exists('zenpipe')) {
    /**
     * Create a new ZenPipe instance.
     *
     * @template T
     * @return ZenPipe<T>
     */
    function zenpipe(): ZenPipe
    {
        return new ZenPipe();
    }
}
