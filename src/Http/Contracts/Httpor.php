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
    public function then(callable $callback): self;

    /**
     * Register failure callback
     *
     * @param callable $callback Function to execute on failure
     * @return self
     */
    public function catch(callable $callback): self;

    /**
     * Download response content to file
     *
     * @param string $savePath Path to save downloaded file
     * @return self
     */
    public function download(string $savePath): self;

    /**
     * Set multipart form data for file uploads
     *
     * @param array $fields Associative array of fields (can include \SplFileInfo for files)
     * @return self
     * @throws \InvalidArgumentException If file is not readable
     */
    public function withMultipart(array $fields): self;

    /**
     * Conditionally modify the request based on a truthy condition
     *
     * @param mixed $condition The condition to evaluate
     * @param callable $callback The modification to apply if condition is truthy
     * @return self
     */
    public function ifSet($condition, callable $callback): self;

    /**
     * Get the raw response body
     *
     * @return string Raw response body content
     */
    public function body(): string;

    /**
     * Get the response body as a stdClass object
     *
     * @return object Response data parsed as object
     */
    public function object(): object;

    /**
     * Get the response data as a collection (optionally for a specific JSON key)
     *
     * @param string|null $key Optional key to extract from JSON response
     * @return mixed Collection instance or null if key doesn't exist
     */
    public function collect(?string $key = null);

    /**
     * Check if the response is a redirect
     *
     * @return bool Whether the response is a redirect (3xx status)
     */
    public function redirect(): bool;

    /**
     * Check if the response indicates a client error (4xx status)
     *
     * @return bool Whether the response has a client error status
     */
    public function clientError(): bool;

    /**
     * Get a specific response header
     *
     * @param string $header Header name to retrieve
     * @return string Header value or empty string if not present
     */
    public function header(string $header): string;

    /**
     * Enable or disable SSL peer verification
     *
     * @param bool $verify Whether to verify SSL peer
     * @return self
     */
    public function withVerifyPeer(bool $verify = true): self;

    /**
     * Configure automatic redirect following
     *
     * @param int $max Maximum number of redirects to follow
     * @return self
     */
    public function withFollowRedirects(int $max = 5): self;

    /**
     * Set a base URL for relative request paths
     *
     * @param string $baseUrl Base URL to prepend to relative paths
     * @return self
     */
    public function withBaseUrl(string $baseUrl): self;

    /**
     * Set multiple request options using a scope/context
     *
     * @param array $options Associative array of scoped options
     * @return self
     */
    public function withScope(array $options): self;

    /**
     * Enable HTTP/2 for the request
     *
     * @param bool $forceHttp2ForHttpUrls Whether to force HTTP/2 even for http:// URLs
     * @return self
     */
    public function withHttp2(bool $forceHttp2ForHttpUrls = false): self;

    /**
     * Disable HTTP/2 for the request
     *
     * @return self
     */
    public function withoutHttp2(): self;
}
