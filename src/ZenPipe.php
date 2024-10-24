<?php

namespace DynamikDev\ZenPipe;

/**
 * @template T
 */
class ZenPipe
{
    /** @var array<callable|array{class-string, string}> */
    protected array $operations = [];

    public function __construct(protected mixed $initialValue = null)
    {
    }

    /**
     * @param mixed|null $initialValue
     * @return self<T>
     */
    public static function make(mixed $initialValue = null): self
    {
        return new self($initialValue);
    }

    /**
     * @param  callable|array{class-string, string}  $operation
     * @return self<T>
     */
    public function pipe($operation): self
    {
        if (is_callable($operation)) {
            $this->operations[] = $operation;
        }

        /**
         * If the operation is an array with two strings, we assume it's a class and method
         */
        if (is_array($operation) && count($operation) === 2 && is_string($operation[0]) && is_string($operation[1])) {
            if (class_exists($operation[0])) {
                /**
                 * @var array{class-string, string} $operation
                 */
                $this->operations[] = $operation;
            } else {
                throw new \InvalidArgumentException('Class '.$operation[0].' does not exist');
            }
        }

        /**
         * If the operation is an array, we need to add each operation to the pipeline
         */
        if (is_array($operation)) {

            /**
             * @var array<callable|array{class-string, string}> $operations
             */
            $operations = $operation;

            foreach ($operations as $op) {
                $this->pipe($op);
            }
        }

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
     * @param  T|null  $initialValue
     * @return T
     * @throws \InvalidArgumentException
     */
    public function process($initialValue = null)
    {
        $value = $initialValue ?? $this->initialValue;

        if ($value === null) {
            throw new \InvalidArgumentException('Initial value must be provided either in the constructor or process method.');
        }

        $pipeline = array_reduce(
            array_reverse($this->operations),
            $this->carry(),
            $this->passThroughOperation()
        );

        return $pipeline($value);
    }

    /**
     * This method is used to carry the value through the pipeline.
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
     * This method is used to pass through the value without any changes.
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
