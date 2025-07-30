<?php

namespace Doppar\Axios\Http;

trait InteractsWithClientScoping
{
    /**
     * Create a new scoped client instance with merged configuration
     *
     * @param array $options
     * @return self
     */
    public function withScope(array $options): self
    {
        $clone = clone $this;

        if (isset($this->options['headers']) && isset($options['headers'])) {
            $options['headers'] = array_merge($this->options['headers'], $options['headers']);
        }

        if (isset($this->options['query']) && isset($options['query'])) {
            $options['query'] = array_merge($this->options['query'], $options['query']);
        }

        $clone->options = array_merge($this->options, $options);

        return $clone;
    }

    /**
     * Create a scoped client with a base URL
     *
     * @param string $baseUrl
     * @return self
     */
    public function withBaseUrl(string $baseUrl): self
    {
        return $this->withScope(['base_uri' => $baseUrl]);
    }

    /**
     * Create a scoped client that automatically follows redirects
     *
     * @param int $max Maximum number of redirects to follow
     * @return self New client instance with redirect following
     */
    public function withFollowRedirects(int $max = 5): self
    {
        return $this->withScope([
            'max_redirects' => $max,
            'follow_redirects' => true
        ]);
    }

    /**
     * Create a scoped client that verifies SSL certificates
     *
     * @param bool $verify Whether to verify SSL certificates
     * @return self New client instance with SSL verification setting
     */
    public function withVerifyPeer(bool $verify = true): self
    {
        return $this->withScope(['verify_peer' => $verify]);
    }

    /**
     * Magic clone method to ensure proper cloning of properties
     */
    public function __clone()
    {
        $this->options = $this->options ?: [];
        $this->middlewares = $this->middlewares ?: [];
        $this->batchUrls = $this->batchUrls ?: [];
        $this->retrySettings = $this->retrySettings ?: [];
    }
}
