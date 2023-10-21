<?php

namespace SequentSoft\ThreadFlowTelegram\HttpClient;

use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;

class GuzzleHttpClientFactory implements HttpClientFactoryInterface
{
    public function create(string $token): HttpClientInterface
    {
        return new GuzzleHttpClient($token);
    }
}
