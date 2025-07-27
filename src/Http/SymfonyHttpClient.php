<?php

namespace Doppar\Axios\Http;

use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\Exception\ClientException;
use Doppar\Axios\Http\Contracts\Httpor;
use Doppar\Axios\Exceptions\NetworkException;

/**
 * SymfonyHttpClient - A robust HTTP client implementation using Symfony's HttpClient component
 *
 * This class provides a fluent interface for making HTTP requests with comprehensive features:
 * - Synchronous and asynchronous request handling
 * - Request retry logic with configurable delays
 * - Batch request processing
 * - Streamed file downloads with progress tracking
 * - Middleware support for request/response processing
 * - Comprehensive error handling and status checking
 *
 * Key Architecture:
 * - Implements Httpor interface for consistent API
 * - Uses Symfony's HttpClient for underlying transport
 * - Maintains separation of sync/async operations
 * - Implements proper resource cleanup
 *
 * @package Doppar\Axios\Http
 */
class SymfonyHttpClient implements Httpor
{
    /** @var array Request configuration options */
    private array $options = [];

    /** @var string Target URL for the request */
    private string $url = '';

    /** @var string HTTP method (GET, POST, etc.) */
    private string $method = 'GET';

    /** @var ResponseInterface|null The last received response */
    private ?ResponseInterface $response = null;

    /** @var array Collection of request/response middlewares */
    private array $middlewares = [];

    /** @var bool Whether requests should be asynchronous */
    private bool $async = false;

    /** @var array Pending asynchronous requests */
    private array $pendingAsyncRequests = [];

    /** @var HttpClientInterface The underlying Symfony HTTP client */
    private HttpClientInterface $client;

    /** @var array URLs for batch processing */
    private array $batchUrls = [];

    /** @var array Retry configuration [max_retries, retry_delay] */
    private array $retrySettings = [];

    /** @var callable|null Progress callback for downloads */
    private $progressCallback = null;

    /**
     * Constructor - Initializes the HTTP client with global options
     *
     * @param array $globalOptions Configuration options applied to all requests:
     *   - timeout: Request timeout in seconds
     *   - headers: Default headers
     *   - Any other Symfony HttpClient options
     */
    public function __construct(array $globalOptions = [])
    {
        $this->client = HttpClient::create($globalOptions);
    }

    /**
     * Set the target URL(s) for the request
     *
     * @param string|array $url Single URL string or array of URLs for batch processing
     * @return self
     */
    public function to(string|array $url): self
    {
        if (is_array($url)) {
            $this->batchUrls = $url;
            return $this;
        }

        $this->url = $url;

        return $this;
    }

    /**
     * Set the HTTP method for the request
     *
     * @param string $method HTTP verb (GET, POST, PUT, etc.)
     * @return self
     */
    public function withMethod(string $method): self
    {
        $this->method = strtoupper($method);

        return $this;
    }

    /**
     * Add headers to the request
     *
     * @param array $headers Associative array of header values
     * @return self
     */
    public function withHeaders(array $headers): self
    {
        $this->options['headers'] = array_merge($this->options['headers'] ?? [], $headers);

        return $this;
    }

    /**
     * Add query parameters to the request
     *
     * @param array $query Associative array of query parameters
     * @return self
     */
    public function withQuery(array $query): self
    {
        $this->options['query'] = $query;

        return $this;
    }

    /**
     * Set raw request body content
     *
     * @param mixed $body Request body content
     * @return self
     */
    public function withBody($body): self
    {
        $this->options['body'] = $body;

        return $this;
    }

    /**
     * Set JSON request body and appropriate Content-Type header
     *
     * @param array $json Data to be JSON encoded
     * @return self
     */
    public function withJson(array $json): self
    {
        $this->options['json'] = $json;

        return $this;
    }

    /**
     * Merge multiple options into the request configuration
     *
     * @param array $options Key-value pairs of request options
     * @return self
     */
    public function withOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Set request asynchronous mode
     *
     * @param bool $async True for async, false for synchronous
     * @return self
     */
    public function async(bool $async = true): self
    {
        $this->async = $async;

        return $this;
    }

    /**
     * Set request timeout in seconds
     *
     * @param float $seconds Timeout duration
     * @return self
     */
    public function timeout(float $seconds): self
    {
        $this->options['timeout'] = $seconds;

        return $this;
    }

    /**
     * Configure automatic retry behavior for failed requests
     *
     * @param int $maxRetries Maximum number of retry attempts
     * @param int $delayMs Delay between retries in milliseconds
     * @return self
     */
    public function retry(int $maxRetries, int $delayMs = 100): self
    {
        $this->retrySettings = [
            'max_retries' => $maxRetries,
            'retry_delay' => $delayMs
        ];

        return $this;
    }

    /**
     * Set Basic Authentication credentials
     *
     * @param string $username Authentication username
     * @param string $password Authentication password
     * @return self
     */
    public function withBasicAuth(string $username, string $password): self
    {
        $this->options['auth_basic'] = [$username, $password];

        return $this;
    }

    /**
     * Set Bearer Token authentication
     *
     * @param string $token Authorization token
     * @return self
     */
    public function withBearerToken(string $token): self
    {
        $this->options['auth_bearer'] = $token;

        return $this;
    }

    /**
     * Add middleware to process requests/responses
     *
     * @param callable $middleware Callback accepting options array
     * @return self
     */
    public function withMiddleware(callable $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * Execute a GET request
     *
     * @return self
     */
    public function get(): self
    {
        return $this->withMethod('GET')->send();
    }

    /**
     * Execute a POST request with optional data
     *
     * @param mixed $data Optional request data
     * @return self
     */
    public function post($data = null): self
    {
        if ($data) {
            $this->withJson($data);
        }

        return $this->withMethod('POST')->send();
    }

    /**
     * Execute a PUT request with optional data
     *
     * @param mixed $data Optional request data
     * @return self
     */
    public function put($data = null): self
    {
        if ($data) {
            $this->withJson($data);
        }

        return $this->withMethod('PUT')->send();
    }

    /**
     * Execute a PATCH request with optional data
     *
     * @param mixed $data Optional request data
     * @return self
     */
    public function patch($data = null): self
    {
        if ($data) {
            $this->withJson($data);
        }

        return $this->withMethod('PATCH')->send();
    }

    /**
     * Execute a DELETE request
     *
     * @return self
     */
    public function delete(): self
    {
        return $this->withMethod('DELETE')->send();
    }

    /**
     * Send the configured HTTP request
     *
     * Handles:
     * - Single and batch requests
     * - Synchronous and asynchronous modes
     * - Automatic retries with delays
     * - Proper error classification
     *
     * @return self
     * @throws NetworkException On network-level failures
     * @throws ClientException On 4xx/5xx responses
     */
    public function send(): self
    {
        try {
            if (!empty($this->batchUrls)) {
                return $this->sendBatch();
            }

            $this->options['buffer'] = false;

            $request = function () {
                $filteredOptions = $this->options;
                unset($filteredOptions['max_retries'], $filteredOptions['retry_delay']);

                return $this->client->request(
                    $this->method,
                    $this->url,
                    $this->applyMiddlewares($filteredOptions)
                );
            };

            if ($this->async) {
                $this->pendingAsyncRequests[] = $request();
                return $this;
            }

            $maxRetries = $this->retrySettings['max_retries'] ?? 0;
            $retryDelay = $this->retrySettings['retry_delay'] ?? 100;
            $retryCount = 0;
            $lastException = null;

            while ($retryCount <= $maxRetries) {
                try {
                    $this->response = $request();

                    if ($this->response->getStatusCode() < 500) {
                        return $this;
                    }

                    throw new ServerException($this->response);
                } catch (TransportException | ServerException $e) {
                    $lastException = $e;
                    $retryCount++;
                    if ($retryCount <= $maxRetries) {
                        usleep($retryDelay * 1000);
                    }
                }
            }

            throw new NetworkException(
                sprintf('Request failed after %d retries', $maxRetries),
                0,
                $lastException
            );
        } catch (ClientException $e) {
            throw new ClientException($e->getCode(), $e->getMessage());
        }
    }

    /**
     * Process batch requests
     *
     * @return self
     * @private
     */
    private function sendBatch(): self
    {
        $requests = [];
        foreach ($this->batchUrls as $url) {
            $requests[] = $this->client->request(
                $this->method,
                $url,
                $this->applyMiddlewares($this->options)
            );
        }

        if ($this->async) {
            $this->pendingAsyncRequests = array_merge($this->pendingAsyncRequests, $requests);
            return $this;
        }

        $this->response = array_map(fn($response) => $response->getContent(), $requests);

        return $this;
    }

    /**
     * Wait for pending asynchronous requests to complete
     *
     * @param bool $asJson Whether to automatically decode responses as JSON
     * @return array Array of responses or exceptions
     */
    public function wait(bool $asJson = false): array
    {
        if (empty($this->pendingAsyncRequests)) {
            return [];
        }

        $responses = [];
        foreach ($this->pendingAsyncRequests as $response) {
            try {
                $responses[] = $asJson
                    ? json_decode($response->getContent(), true)
                    : $response;
            } catch (\Exception $e) {
                $responses[] = $e;
            }
        }

        $this->pendingAsyncRequests = [];

        return $responses;
    }

    /**
     * Get response body as decoded JSON
     *
     * @return array
     * @throws \RuntimeException If no response available
     */
    public function json(): array
    {
        if (!empty($this->batchUrls) && $this->async) {
            throw new \RuntimeException('For async batch requests, call wait() first before json()');
        }

        if (is_array($this->response)) {
            return array_map(fn($content) => json_decode($content, true), $this->response);
        }

        $this->ensureResponse();

        return $this->response->toArray();
    }

    /**
     * Get raw response body content
     *
     * @return string
     * @throws \RuntimeException If no response available
     */
    public function text(): string
    {
        $this->ensureResponse();

        return $this->response->getContent();
    }

    /**
     * Get HTTP status code
     *
     * @return int
     * @throws \RuntimeException If no response available
     */
    public function status(): int
    {
        $this->ensureResponse();

        return $this->response->getStatusCode();
    }

    /**
     * Check if request was successful (2xx status)
     *
     * @return bool
     */
    public function successful(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * Check if request failed (4xx or 5xx status)
     *
     * @return bool
     */
    public function failed(): bool
    {
        return $this->status() >= 400;
    }

    /**
     * Get response headers
     *
     * @return array
     * @throws \RuntimeException If no response available
     */
    public function headers(): array
    {
        $this->ensureResponse();

        return $this->response->getHeaders();
    }

    /**
     * Register success callback
     *
     * @param callable $callback Function to execute on successful response
     * @return self
     */
    public function onSuccess(callable $callback): self
    {
        if ($this->successful()) {
            $callback($this->response);
        }

        return $this;
    }

    /**
     * Register failure callback
     *
     * @param callable $callback Function to execute on failed response
     * @return self
     */
    public function onFailure(callable $callback): self
    {
        if ($this->failed()) {
            $callback($this->response);
        }

        return $this;
    }

    /**
     * Apply registered middlewares to request options
     *
     * @param array $options Original request options
     * @return array Processed options
     * @private
     */
    private function applyMiddlewares(array $options): array
    {
        foreach ($this->middlewares as $middleware) {
            $options = $middleware($options);
        }

        return $options;
    }

    /**
     * Verify response is available
     *
     * @throws \RuntimeException If no response exists
     * @private
     */
    private function ensureResponse(): void
    {
        if ($this->response === null) {
            throw new \RuntimeException('No response available. Did you send the request?');
        }
    }

    /**
     * Register download progress callback
     *
     * @param callable $callback Function receiving (downloadedBytes, totalBytes)
     * @return self
     */
    public function withProgress(callable $callback): self
    {
        $this->progressCallback = $callback;

        return $this;
    }

    /**
     * Download response content to file
     *
     * Features:
     * - Streamed download for memory efficiency
     * - Progress reporting
     * - Automatic directory creation
     * - Validation of download completeness
     * - Cleanup on failure
     *
     * @param string $savePath File path to save download
     * @return self
     * @throws NetworkException On download failures
     * @throws ClientException On HTTP errors
     */
    public function download(string $savePath): self
    {
        $this->ensureResponse();

        if ($this->failed()) {
            throw new ClientException(
                $this->status(),
                sprintf('Download failed with status code: %d', $this->status())
            );
        }

        $contentType = $this->response->getHeaders()['content-type'][0] ?? '';
        if (strpos($contentType, 'text/html') !== false && $this->status() === 200) {
            throw new NetworkException('Unexpected HTML response - possible redirect');
        }

        $dir = dirname($savePath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new NetworkException(sprintf('Directory "%s" could not be created', $dir));
        }

        $fileHandler = @fopen($savePath, 'w');
        if ($fileHandler === false) {
            throw new NetworkException(sprintf('Could not open file "%s" for writing', $savePath));
        }

        try {
            $downloaded = 0;
            $contentLength = (int)($this->response->getHeaders()['content-length'][0] ?? 0);

            foreach ($this->client->stream($this->response) as $chunk) {
                if ($chunk->isTimeout()) {
                    continue;
                }

                if ($chunk->isFirst()) {
                    if ($this->response->getStatusCode() >= 400) {
                        throw new ClientException(
                            $this->response->getStatusCode(),
                            'Server returned error status'
                        );
                    }
                }

                $content = $chunk->getContent();
                if ($content !== '') {
                    if (fwrite($fileHandler, $content) === false) {
                        throw new NetworkException('Failed to write to file');
                    }
                    $downloaded += strlen($content);

                    if ($this->progressCallback) {
                        call_user_func(
                            $this->progressCallback,
                            $downloaded,
                            $contentLength > 0 ? $contentLength : null
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            fclose($fileHandler);
            @unlink($savePath);
            throw $e;
        } finally {
            if (is_resource($fileHandler)) {
                fclose($fileHandler);
            }
        }

        if ($contentLength > 0 && $downloaded !== $contentLength) {
            @unlink($savePath);
            throw new NetworkException(sprintf(
                'Download incomplete. Expected %d bytes, got %d bytes',
                $contentLength,
                $downloaded
            ));
        }

        return $this;
    }
}
