<?php

namespace Doppar\Axios\Http\Contracts;

interface Httpor
{
    /**
     * Set the target URL for the request
     *
     * @param string $url The complete request URL
     * @return self
     */
    public function to(string $url): self;

    /**
     * Set the HTTP method for the request
     *
     * @param string $method HTTP method (GET, POST, PUT, etc.)
     * @return self
     */
    public function withMethod(string $method): self;

    /**
     * Add headers to the request
     *
     * @param array $headers Associative array of headers
     * @return self
     */
    public function withHeaders(array $headers): self;

    /**
     * Add query parameters to the request
     *
     * @param array $query Associative array of query parameters
     * @return self
     */
    public function withQuery(array $query): self;

    /**
     * Set the raw request body
     *
     * @param mixed $body Request body content
     * @return self
     */
    public function withBody($body): self;

    /**
     * Set JSON request body (automatically sets Content-Type header)
     *
     * @param array $json Data to be encoded as JSON
     * @return self
     */
    public function withJson(array $json): self;

    /**
     * Set multiple options at once
     *
     * @param array $options Associative array of request options
     * @return self
     */
    public function withOptions(array $options): self;

    /**
     * Set request as asynchronous
     *
     * @param bool $async Whether to make the request asynchronous
     * @return self
     */
    public function async(bool $async = true): self;

    /**
     * Set request timeout in seconds
     *
     * @param float $seconds Timeout duration
     * @return self
     */
    public function timeout(float $seconds): self;

    /**
     * Configure request retry behavior
     *
     * @param int $maxRetries Maximum number of retry attempts
     * @param int $delayMs Delay between retries in milliseconds
     * @return self
     */
    public function retry(int $maxRetries, int $delayMs = 100): self;

    /**
     * Set Basic Authentication credentials
     *
     * @param string $username Authentication username
     * @param string $password Authentication password
     * @return self
     */
    public function withBasicAuth(string $username, string $password): self;

    /**
     * Set Bearer Token authentication
     *
     * @param string $token Authorization token
     * @return self
     */
    public function withBearerToken(string $token): self;

    /**
     * Add request middleware
     *
     * @param callable $middleware Middleware callback function
     * @return self
     */
    public function withMiddleware(callable $middleware): self;

    /**
     * Execute a GET request
     *
     * @return self
     */
    public function get(): self;

    /**
     * Execute a POST request
     *
     * @param mixed $data Optional request data
     * @return self
     */
    public function post($data = null): self;

    /**
     * Execute a PUT request
     *
     * @param mixed $data Optional request data
     * @return self
     */
    public function put($data = null): self;

    /**
     * Execute a PATCH request
     *
     * @param mixed $data Optional request data
     * @return self
     */
    public function patch($data = null): self;

    /**
     * Execute a DELETE request
     *
     * @return self
     */
    public function delete(): self;

    /**
     * Send the configured request
     *
     * @return self
     */
    public function send(): self;

    /**
     * Wait for asynchronous requests to complete
     *
     * @return array Array of responses
     */
    public function wait(): array;

    /**
     * Get response body as decoded JSON
     *
     * @return array Decoded JSON response
     */
    public function json(): array;

    /**
     * Get response body as raw text
     *
     * @return string Response content
     */
    public function text(): string;

    /**
     * Get response status code
     *
     * @return int HTTP status code
     */
    public function status(): int;

    /**
     * Check if request was successful (2xx status)
     *
     * @return bool Whether request succeeded
     */
    public function successful(): bool;

    /**
     * Check if request failed (4xx or 5xx status)
     *
     * @return bool Whether request failed
     */
    public function failed(): bool;

    /**
     * Get response headers
     *
     * @return array Associative array of response headers
     */
    public function headers(): array;

    /**
     * Register success callback
     *
     * @param callable $callback Function to execute on success
     * @return self
     */
    public function onSuccess(callable $callback): self;

    /**
     * Register failure callback
     *
     * @param callable $callback Function to execute on failure
     * @return self
     */
    public function onFailure(callable $callback): self;

    /**
     * Download response content to file
     *
     * @param string $savePath Path to save downloaded file
     * @return self
     */
    public function download(string $savePath): self;
}
