<?php

namespace Doppar\Axios\Http;

use Phaseolies\Support\Collection;
use RuntimeException;

trait InteractsWithResponse
{
    /**
     * Get the raw response body as string
     *
     * @return string
     * @throws RuntimeException If no response available
     */
    public function body(): string
    {
        $this->ensureResponse();

        return $this->response->getContent();
    }

    /**
     * Get the response body as decoded object
     *
     * @return object
     * @throws RuntimeException If no response available or invalid JSON
     */
    public function object(): object
    {
        $this->ensureResponse();

        $content = $this->response->getContent();

        $decoded = json_decode($content);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to decode JSON: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Get the response as a Collection
     *
     * @param string|null $key Optional key to extract from response
     * @return Collection
     * @throws RuntimeException If no response available
     */
    public function collect(?string $key = null): Collection
    {
        $data = $this->json();

        if ($key !== null) {
            if (!isset($data[$key])) {
                throw new RuntimeException("Key '{$key}' not found in response");
            }
            $data = $data[$key];
        }

        return new Collection(static::class, is_array($data) ? $data : [$data]);
    }

    /**
     * Check if request was successful (2xx status)
     *
     * @return bool
     * @throws RuntimeException If no response available
     */
    public function successful(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    /**
     * Check if response is a redirect
     *
     * @return bool
     * @throws RuntimeException If no response available
     */
    public function redirect(): bool
    {
        $status = $this->status();

        return $status >= 300 && $status < 400;
    }

    /**
     * Check if request failed (4xx or 5xx status)
     * 
     * @return bool
     * @throws RuntimeException If no response available
     */
    public function failed(): bool
    {
        return $this->status() >= 400;
    }

    /**
     * Check if response is a client error (4xx status)
     *
     * @return bool
     * @throws RuntimeException If no response available
     */
    public function clientError(): bool
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    /**
     * Get a specific header value
     *
     * @param string $header Header name
     * @return string
     * @throws RuntimeException If no response available or header not found
     */
    public function header(string $header): string
    {
        $headers = $this->headers();

        if (!isset($headers[$header])) {
            throw new RuntimeException("Header '{$header}' not found in response");
        }

        return is_array($headers[$header]) ? $headers[$header][0] : $headers[$header];
    }

    /**
     * Get all response headers
     *
     * @return array
     * @throws RuntimeException If no response available
     */
    public function headers(): array
    {
        $this->ensureResponse();

        return $this->response->getHeaders();
    }

    /**
     * Ensure response is available
     * 
     * @throws RuntimeException If no response exists
     */
    private function ensureResponse(): void
    {
        if ($this->response === null) {
            throw new RuntimeException('No response available. Did you send the request?');
        }
    }
}
