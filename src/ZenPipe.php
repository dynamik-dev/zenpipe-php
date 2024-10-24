<?php

namespace DynamikDev\ZenPipe;

/**
 * @template T
 */
class ZenPipe
{
    /** @var array<callable|array{class-string, string}> */
    protected array $operations = [];

    /**
     * @return self<T>
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * @param  callable|array{class-string, string}  $operation
     * @return self<T>
     */
    public function pipe($operation): self
    {
        $this->operations[] = $operation;

        return $this;
    }

    /**
     * @param  T  $initialValue
     * @return T
     */
    public function __invoke($initialValue)
    {
        return $this->process($initialValue);
    }

    /**
     * @param  T  $initialValue
     * @return T
     */
    protected function process($initialValue)
    {
        $pipeline = array_reduce(
            array_reverse($this->operations),
            $this->carry(),
            $this->passThroughOperation()
        );

        return $pipeline($initialValue);
    }

    /**
     * This function is used to carry the value through the pipeline.
     * It wraps the next operation in a closure that can handle both
     * static method calls and regular callables.
     *
     * @return callable
     */
    protected function carry(): callable
    {
        return function ($next, $operation) {
            return function ($value) use ($next, $operation) {
                if (is_array($operation) && count($operation) === 2 && is_string($operation[0]) && is_string($operation[1])) {
                    $class = $operation[0];
                    $method = $operation[1];
                    $instance = new $class();

                    return $instance->$method($value, $next);
                }

                return $operation($value, $next);
            };
        };
    }

    /**
     * This function is used to pass through the value without any changes.
     *
     * @return callable
     */
    protected function passThroughOperation(): callable
    {
        return function ($value) {
            return $value;
        };
    }
}
