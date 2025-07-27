<?php

namespace Doppar\Axios\Http;

use Doppar\Axios\Http\SymfonyHttpClient;
use Doppar\Axios\Http\Contracts\Httpor;

class Axios
{
    /**
     * Singleton instance for synchronous requests
     *
     * @var Httpor|null
     */
    private static ?Httpor $impl = null;

    /**
     * Global configuration options applied to all requests
     *
     * @var array
     */
    private static array $globalOptions = [];

    /**
     * Singleton instance for asynchronous requests
     *
     * @var Httpor|null
     */
    private static ?Httpor $asyncImpl = null;

    /**
     * Configure global options for all HTTP requests
     *
     * @param array $options Key-value pairs of HTTP client options
     * @return void
     */
    public static function configure(array $options): void
    {
        self::$globalOptions = $options;
    }

    /**
     * Get or create the appropriate HTTP client instance
     *
     * @param bool $forAsync Whether to get the async client instance
     * @return Httpor Configured HTTP client implementation
     */
    private static function make(bool $forAsync = false): Httpor
    {
        if ($forAsync) {
            if (self::$asyncImpl === null) {
                self::$asyncImpl = new SymfonyHttpClient(self::$globalOptions);
            }
            return self::$asyncImpl;
        }

        if (self::$impl === null) {
            self::$impl = new SymfonyHttpClient(self::$globalOptions);
        }
        return self::$impl;
    }

    /**
     * Wait for pending asynchronous requests to complete
     *
     * @param bool $asJson Whether to automatically decode responses as JSON
     * @return array Array of responses or exceptions
     */
    public static function wait(bool $asJson = false): array
    {
        if (self::$asyncImpl === null) {
            return [];
        }
        return self::$asyncImpl->wait($asJson);
    }

    /**
     * Handle static method calls and route to appropriate client instance
     *
     * @param string $method Method name being called
     * @param array $arguments Method arguments
     * @return mixed Method result from the HTTP client implementation
     * @throws \BadMethodCallException When method doesn't exist on implementation
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $isAsync = $method === 'async' ||
            (count($arguments) > 0 && $arguments[0] === true);

        $impl = self::make($isAsync);

        if (!method_exists($impl, $method)) {
            throw new \BadMethodCallException("Method '$method' not found on Axios");
        }

        return $impl->$method(...$arguments);
    }
}
