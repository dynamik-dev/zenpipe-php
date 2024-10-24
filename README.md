# ZenPipe

<div style="display: flex; flex-direction: row; align-items: center; gap: 10px; margin-bottom: 10px;">
  <a href="https://github.com/dynamik-dev/zenpipe-php/actions/workflows/CI.yml">
    <img src="https://github.com/dynamik-dev/zenpipe-php/actions/workflows/CI.yml/badge.svg" alt="GitHub Actions">
  </a>
  <a href="https://buymeacoffee.com/chrisarter">
    <img src="https://raw.githubusercontent.com/pachadotdev/buymeacoffee-badges/main/bmc-yellow.svg" alt="BuyMeACoffee">
  </a>
</div>

<p align="center">
  <img src="./zenpipe.png" alt="ZenPipe Logo" style="max-width: 30rem;">
</p>

ZenPipe is a simple and flexible PHP pipeline library that allows you to chain operations together to process, transform, or act on input.

```php
$calculator = zenpipe()
   ->pipe(fn($price, $next) => $next($price * 0.8)) // 20% discount  
   ->pipe(fn($price, $next) => $next($price * 1.1)); // add 10% tax

$calculator(100); // $88 (100 -> 80 -> 88)
```

You can also run the pipeline on demand:

```php
zenpipe(100)
   ->pipe(fn($price, $next) => $next($price * 0.8)) // 20% discount  
   ->pipe(fn($price, $next) => $next($price * 1.1)) // add 10% tax
   ->process(); // 88
```

## Sections

1. [ZenPipe](#zenpipe)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Usage](#usage)
   - [Pipeline Operations](#pipeline-operations)
   - [Class Methods as Operations](#class-methods-as-operations)
5. [Examples](#examples)
   - [RAG Processes](#rag-processes)
   - [Email Validation](#email-validation)
6. [Contributing](#contributing)
7. [License](#license)
8. [Roadmap](#roadmap)

## Requirements

- PHP 8.2 or higher

## Installation

```bash
composer require dynamik-dev/zenpipe-php
```
## Usage
### Pipeline Operations

Pipeline operations are functions that take an input and return a processed value. They can be passed as a single function or as an array with the class name and method name.

```php
$pipeline = zenpipe()
   ->pipe(fn($input, $next) => $next(strtoupper($input)));
```

### Class Methods as Operations

You can also use class methods as operations:

```php
class MyClass
{
    public function uppercase($input)
    {
        return strtoupper($input);
    }
}

$pipeline = zenpipe()
   ->pipe([MyClass::class, 'uppercase']);
```

You can also pass an array of operations:

```php
$pipeline = zenpipe()
   ->pipe([
        fn($input, $next) => $next(strtoupper($input)),
        [MyClass::class, 'uppercase']
    ]);
```

### Examples

#### RAG Processes

This pipeline can be used for RAG processes, where the output of one model is used as input for another.

```php
$ragPipeline = zenpipe()
    ->pipe(fn($query, $next) => $next([
        'query' => $query,
        'embeddings' => OpenAI::embeddings()->create([
            'model' => 'text-embedding-3-small',
            'input' => $query
        ])->embeddings[0]->embedding
    ]))
    ->pipe(fn($data, $next) => $next([
        ...$data,
        'context' => Qdrant::collection('knowledge-base')
            ->search($data['embeddings'], limit: 3)
            ->map(fn($doc) => $doc->content)
            ->join("\n")
    ]))
    ->pipe(fn($data, $next) => $next(
        OpenAI::chat()->create([
            'model' => 'gpt-4-turbo-preview',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Answer using the provided context only.'
                ],
                [
                    'role' => 'user',
                    'content' => "Context: {$data['context']}\n\nQuery: {$data['query']}"
                ]
            ]
        ])->choices[0]->message->content
    ));

$answer = $ragPipeline("What's our refund policy?");
```

#### Email Validation

This pipeline can be used to validate an email address.

```php
   $emailValidationPipeline = zenpipe()
    ->pipe(function($input, $next) {
        return $next(filter_var($input, FILTER_VALIDATE_EMAIL));
    })
    ->pipe(function($email, $next) {

        if (!$email) {
            return false;
        }
        
        $domain = substr(strrchr($email, "@"), 1);
        $mxhosts = [];
        $mxweight = [];
        
        if (getmxrr($domain, $mxhosts, $mxweight)) {
            return $next(true);
        }
        
        // If MX records don't exist, check for A record as a fallback
        return $next(checkdnsrr($domain, 'A'));
    });


$result = $emailValidationPipeline('example@example.com');
``` 

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.

## Roadmap

- [ ] Add support for PSR-15 middleware

