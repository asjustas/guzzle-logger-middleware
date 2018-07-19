<?php

include 'vendor/autoload.php';

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise;

class LoggerMiddleware {
    /** @var callable */
    private $callback;

    /** @var bool */
    private $enabled;

    /** @var array */
    private $context;

    /**
     * LoggerMiddleware constructor.
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(callable $handler)
    {
        return function ($request, array $options) use ($handler) {
            $this->setOptions($options);

            return $handler($request, $options)->then(
                $this->onSuccess($request),
                $this->onFailure($request)
            );
        };
    }

    protected function onSuccess(RequestInterface $request)
    {
        return function ($response) use ($request) {
            if ($this->enabled) {
                $this->log($request, $response);
            }

            return $response;
        };
    }

    protected function onFailure(RequestInterface $request)
    {
        return function ($reason) use ($request) {
            if ($this->enabled) {
                $response = null;

                if ($reason instanceof RequestException) {
                    $response = $reason->getResponse();
                }

                $this->log($request, $response);
            }

            return Promise\rejection_for($reason);
        };
    }

    protected function log(RequestInterface $request, ResponseInterface $response = null)
    {
        call_user_func(
            $this->callback,
            $request,
            $response,
            $this->context
        );
    }

    private function setOptions(array $options)
    {
        if (!isset($options['logger'])) {
            return;
        }

        $this->enabled = $options['logger']['enabled'] ?? false;
        $this->context = $options['logger']['context'] ?? [];
    }
}

try {
    $stack = HandlerStack::create();
    $stack
        ->push(
            new LoggerMiddleware(
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

    $client = new \GuzzleHttp\Client();

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
