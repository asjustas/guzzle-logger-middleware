<?php

namespace AJ\Guzzle\Middleware;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise;

class Logger
{
    /** @var callable */
    private $callback;

    /** @var bool */
    private $enabled;

    /** @var array */
    private $context;

    /**
     * Logger constructor.
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param callable $handler
     *
     * @return \Closure
     */
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

    /**
     * @param RequestInterface $request
     *
     * @return \Closure
     */
    protected function onSuccess(RequestInterface $request)
    {
        return function ($response) use ($request) {
            if ($this->enabled) {
                $this->log($request, $response);
            }

            return $response;
        };
    }

    /**
     * @param RequestInterface $request
     *
     * @return \Closure
     */
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

    /**
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     */
    protected function log(RequestInterface $request, ResponseInterface $response = null): void
    {
        call_user_func(
            $this->callback,
            $request,
            $response,
            $this->context
        );
    }

    /**
     * @param array $options
     */
    private function setOptions(array $options): void
    {
        if (!isset($options['logger'])) {
            return;
        }

        $this->enabled = $options['logger']['enabled'] ?? false;
        $this->context = $options['logger']['context'] ?? [];
    }
}
