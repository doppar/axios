<?php

namespace Doppar\Axios\Http;

/**
 * Static proxy class for Doppar axios
 *
 * @method static to(string $url): self;
 * @method static withMethod(string $method): self;
 * @method static withHeaders(array $headers): self;
 * @method static withQuery(array $query): self;
 * @method static withBody($body): self;
 * @method static withJson(array $json): self;
 * @method static withOptions(array $options): self;
 * @method static async(bool $async = true): self;
 * @method static timeout(float $seconds): self;
 * @method static retry(int $maxRetries, int $delayMs = 100): self;
 * @method static withBasicAuth(string $username, string $password): self;
 * @method static withBearerToken(string $token): self;
 * @method static withMiddleware(callable $middleware): self;
 * @method static get(): self;
 * @method static post($data = null): self;
 * @method static put($data = null): self;
 * @method static patch($data = null): self;
 * @method static delete(): self;
 * @method static send(): self;
 * @method static wait(): array;
 * @method static json(): array;
 * @method static body(): string;
 * @method static status(): int;
 * @method static successful(): bool;
 * @method static failed(): bool;
 * @method static object(): object;
 * @method static collect(?string $key = null);
 * @method static redirect(): bool;
 * @method static clientError(): bool;
 * @method static headers(): array;
 * @method static header(string $header): string;
 * @method static then(callable $callback): self;
 * @method static catch(callable $callback): self;
 * @method static download(string $savePath): self;
 * @method static withMultipart(array $fields): self
 * @method static withProgress(callable $callback): self
 * @method static ifSet($condition, callable $callback): self
 * @method static withVerifyPeer(bool $verify = true): self;
 * @method static withFollowRedirects(int $max = 5): self;
 * @method static withBaseUrl(string $baseUrl): self;
 * @method static withScope(array $options): self;
 * @method static withHttp2(bool $forceHttp2ForHttpUrls = false): self;
 * @method static withoutHttp2(): self;
 *
 * @see \Doppar\Axios\Http\SymfonyHttpClient The underlying implementation
 * @package Doppar\Axios\Http
 */

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
