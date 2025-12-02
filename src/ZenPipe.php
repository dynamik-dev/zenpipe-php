<?php

namespace DynamikDev\ZenPipe;

/**
 * @template T
 * @template TContext
 */
class ZenPipe
{
    /** @var array<callable|array{class-string, string}> */
    protected array $operations = [];

    /** @var TContext|null */
    protected mixed $context = null;

    /** @var callable|null */
    protected $exceptionHandler = null;

    public function __construct(protected mixed $initialValue = null)
    {
    }

    /**
     * Set an exception handler for the pipeline.
     *
     * @param  callable(\Throwable, mixed, TContext|null): mixed  $handler
     * @return self<T, TContext>
     */
    public function catch(callable $handler): self
    {
        $this->exceptionHandler = $handler;

        return $this;
    }

    /**
     * Set the context to be passed to each operation.
     *
     * @template TNewContext
     *
     * @param  TNewContext  $context
     * @return self<T, TNewContext>
     */
    public function withContext(mixed $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @param mixed|null $initialValue
     * @return self<T, TContext>
     */
    public static function make(mixed $initialValue = null): self
    {
        return new self($initialValue);
    }

    /**
     * @param  callable|array{class-string, string}  $operation
     * @return self<T, TContext>
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
     * @throws \Throwable
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

        try {
            return $pipeline($value);
        } catch (\Throwable $e) {
            if ($this->exceptionHandler !== null) {
                return ($this->exceptionHandler)($e, $value, $this->context);
            }
            throw $e;
        }
    }

    /**
     * This method is used to carry the value through the pipeline.
     * It wraps the next operation in a closure that can handle both
     * static method calls and regular callables.
     *
     * Operations can accept up to four parameters:
     * - For callables: function($value, $next, $return, $context)
     * - For class methods: method($value, $next, $return, $context)
     *
     * @return callable
     */
    public function carry(): callable
    {
        return function ($next, $operation) {
            return function ($value) use ($next, $operation) {
                $return = function ($value) {
                    return $value;
                };

                if (is_array($operation) && count($operation) === 2 && is_string($operation[0]) && is_string($operation[1])) {
                    $class = $operation[0];
                    $method = $operation[1];
                    $instance = new $class();

                    return $instance->$method($value, $next, $return, $this->context);
                }

                return $operation($value, $next, $return, $this->context);
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
