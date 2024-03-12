<?php

namespace SequentSoft\ThreadFlowTelegram\Contracts\HttpClient;

interface HttpClientInterface
{
    public function getBaseUri(string $token): string;

    public function getBaseFileDownloadUri(string $token): string;

    public function postJson(string $endpoint, array $payload): ResponseInterface;

    public function postMultipart(string $endpoint, array $payload): ResponseInterface;
}
