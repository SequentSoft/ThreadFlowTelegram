<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits;

use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientFactoryInterface;

trait GetFileTrait
{
    protected ?string $botToken = null;

    protected HttpClientFactoryInterface $httpClientFactory;

    public function getApiToken(): string
    {
        return $this->botToken;
    }

    public function setApiToken(string $apiToken): void
    {
        $this->botToken = $apiToken;
    }

    public function getHttpClientFactory(): HttpClientFactoryInterface
    {
        return $this->httpClientFactory;
    }

    public function setHttpClientFactory(HttpClientFactoryInterface $httpClientFactory): void
    {
        $this->httpClientFactory = $httpClientFactory;
    }

    public function getTelegramFileData(string $fileId): array
    {
        $response = $this->httpClientFactory->create($this->botToken)->postJson('getFile', [
            'file_id' => $fileId,
        ])->getParsedDataResult();

        return  $response['result'];
    }

    public function getTelegramFileUrl(string $fileId): string
    {
        $data = $this->getTelegramFileData($fileId);

        $baseUri = trim($this->httpClientFactory->create($this->botToken)->getBaseUri($this->botToken), '/') . '/';

        return $baseUri . $data['file_path'];
    }
}
