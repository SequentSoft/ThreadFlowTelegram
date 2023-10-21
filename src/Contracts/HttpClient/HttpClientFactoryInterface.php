<?php

namespace SequentSoft\ThreadFlowTelegram\Contracts\HttpClient;

interface HttpClientFactoryInterface
{
    public function create(string $token): HttpClientInterface;
}
