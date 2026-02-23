<?php

namespace App\Service\Admin\ShippingEasy;

class Signature
{
    private string $apiSecret;

    private string $httpMethod;

    private string $path;

    private array $params;

    private ?array $body;

    public function getApiSecret(): string
    {
        return $this->apiSecret;
    }

    public function setApiSecret(string $apiSecret): void
    {
        $this->apiSecret = $apiSecret;
    }

    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    public function setHttpMethod(string $httpMethod): void
    {
        $this->httpMethod = $httpMethod;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): void
    {
        ksort($params);
        $this->params = $params;
    }

    public function getBody(): ?array
    {
        return $this->body;
    }

    public function setBody(?array $body): void
    {
        $this->body = $body;
    }

    public function queryString(): string
    {
        $parts = array($this->getHttpMethod());
        $parts[] = $this->getPath();

        if (!empty($this->getParams()))
            $parts[] = http_build_query($this->getParams());

        if (is_array($this->getBody()) && !empty($this->getBody())) {
            $parts[] = json_encode($this->getBody());
        }

        return implode("&", $parts);
    }

    public function encrypted(): string
    {
        return hash_hmac('SHA256', $this->queryString(), $this->getApiSecret());
    }

    public function equals($signature): bool
    {
        return $this->encrypted() == $signature;
    }
}