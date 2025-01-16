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
- **Returns:** The `ZenPipe` instance for method chaining.
- **Throws:** `\InvalidArgumentException` if the specified class does not exist.

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
