# DynamikDev ZenPipe

## Installation

```bash
composer require dynamik-dev/zenpipe-php
```

## Usage

### Basic Usage

```php
use DynamikDev\ZenPipe\Pipeline;

$result = Pipeline::make()
    ->pipe(function ($value) {
        return $value * 2;
    })
    ->pipe(function ($value) {
        return $value + 1;
    })
    ->invoke(5);

echo $result; // Outputs: 11
```

### Using Static Methods

```php
class MathOperations
{
    public static function double($value)
    {
        return $value * 2;
    }
}

$result = Pipeline::make()
    ->pipe([MathOperations::class, 'double'])
    ->invoke(5);

echo $result; // Outputs: 10
```

### Using Instance Methods

```php
class StringOperations
{
    public function uppercase($value)
    {
        return strtoupper($value);
    }
}

$stringOps = new StringOperations();

$result = Pipeline::make()
    ->pipe([$stringOps, 'uppercase'])
    ->invoke('hello');

echo $result; // Outputs: HELLO
```

### Combining Different Types of Operations

```php
$stringOps = new StringOperations();

$result = Pipeline::make()
    ->pipe(function ($value) {
        return $value * 2;
    })
    ->pipe([MathOperations::class, 'double'])
    ->pipe([$stringOps, 'uppercase'])
    ->invoke(5);

echo $result; // Outputs: 20
```
