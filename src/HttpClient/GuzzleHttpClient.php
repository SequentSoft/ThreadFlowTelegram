<?php

namespace SequentSoft\ThreadFlowTelegram\HttpClient;

use GuzzleHttp\Client;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\ResponseInterface;

class GuzzleHttpClient implements HttpClientInterface
{
    protected Client $client;

    public function __construct(string $token)
    {
        $this->client = new Client([
            'base_uri' => $this->getBaseUri($token),
        ]);
    }

    public function getBaseUri(string $token): string
    {
        return "https://api.telegram.org/bot{$token}/";
    }

    public function getBaseFileDownloadUri(string $token): string
    {
        return "https://api.telegram.org/file/bot{$token}/";
    }

    public function postJson(string $endpoint, array $payload): ResponseInterface
    {
        $response = $this->client->post($endpoint, [
            'json' => $payload,
        ]);

        return new Response(
            $response->getBody()->getContents(),
            $response->getStatusCode()
        );
    }

    public function postMultipart(string $endpoint, array $payload): ResponseInterface
    {
        $response = $this->client->post($endpoint, [
            'multipart' => $payload,
        ]);

        return new Response(
            $response->getBody()->getContents(),
            $response->getStatusCode()
        );
    }
}
