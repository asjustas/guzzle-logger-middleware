<?php

include 'vendor/autoload.php';

use GuzzleHttp\HandlerStack;
use AJ\Guzzle\Middleware\Logger;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client;

try {
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

    $client = new Client();

    $client
        ->request(
            'POST',
            'http://example.com/404',
            [
                'body' => 'Hello World',
                'handler' => $stack,
                'logger' => [
                    'enabled' => true,
                    'context' => [
                        'something' => 'important',
                    ],
                ],
                'timeout' => 0.1, // Response timeout
                'connect_timeout' => 0.1, // Connection timeout
            ]
        );
} catch (Throwable $e) {
    echo $e->getMessage();
}
