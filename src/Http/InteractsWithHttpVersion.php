<?php

namespace Doppar\Axios\Http;

trait InteractsWithHttpVersion
{
    /** @var string|null HTTP version to use (null for auto, '1.1' or '2.0') */
    private ?string $httpVersion = null;

    /**
     * Enable HTTP/2 support
     *
     * @param bool $forceHttp2ForHttpUrls Whether to force HTTP/2 for non-HTTPS URLs
     * @return self
     */
    public function withHttp2(bool $forceHttp2ForHttpUrls = false): self
    {
        $clone = clone $this;

        $clone->httpVersion = '2.0';

        if ($forceHttp2ForHttpUrls) {
            $clone->options['http_version'] = '2.0';
        }

        return $clone;
    }

    /**
     * Disable HTTP/2 support (force HTTP/1.1)
     *
     * @return self
     */
    public function withoutHttp2(): self
    {
        $clone = clone $this;

        $clone->httpVersion = '1.1';

        $clone->options['http_version'] = '1.1';

        return $clone;
    }

    /**
     * Prepare HTTP version options for the request
     *
     * @return array
     */
    private function prepareHttp2Options(): array
    {
        if ($this->httpVersion === null) {
            return [];
        }

        return [
            'http_version' => $this->httpVersion
        ];
    }
}
