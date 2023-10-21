<?php

namespace SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming;

use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientFactoryInterface;

interface InteractsWithHttpInterface
{
    public function getApiToken(): string;

    public function setApiToken(string $apiToken): void;

    public function setHttpClientFactory(HttpClientFactoryInterface $httpClientFactory): void;

    public function getHttpClientFactory(): HttpClientFactoryInterface;
}
