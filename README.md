# Guzzle logger middleware

[![Latest Stable Version](https://poser.pugx.org/asjustas/guzzle-logger-middleware/v/stable)](https://packagist.org/packages/guzzle-logger-middleware)
[![Latest Unstable Version](https://poser.pugx.org/asjustas/guzzle-logger-middleware/v/unstable)](https://packagist.org/packages/guzzle-logger-middleware)

Guzzle middleware to log requests and responses.

## Installation

Install via composer:

 `composer require asjustas/guzzle-logger-middleware`
 
## Usage

```php
<?php

use GuzzleHttp\HandlerStack;
use AJ\Guzzle\Middleware\Logger;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client;

$stack = HandlerStack::create();
$stack
    ->push(
        new Logger(
            function (RequestInterface $request, ?ResponseInterface $response, array $context) {
                print_r(
                    [
                        'yes' => true,
                        'uri' => (string)$request->getUri(),
                        'req' => (string)$request->getBody(),
                        'res' => $response ? (string)$response->getBody() : '',
                        'context' => $context,
                    ]
                );
            }
        )
    );

$client = new Client([
    'handler' => $stack,
]);

$client
    ->request(
        'POST',
        'http://example.com/404',
        [
            'body' => 'Hello World',
            'logger' => [
                'enabled' => true,
                'context' => [
                    'something' => 'important',
                ],
            ],
        ]
    );
```

## Based upon

- https://github.com/rtheunissen/guzzle-log-middleware
- https://github.com/gmponos/Guzzle-logger