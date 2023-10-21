<?php

namespace SequentSoft\ThreadFlowTelegram\HttpClient;

use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\ResponseInterface;

class Response implements ResponseInterface
{
    public function __construct(
        protected string $rawData,
        protected int $statusCode,
    ) {
    }

    public function getRawData(): string
    {
        return $this->rawData;
    }

    public function getParsedData(): array
    {
        return json_decode(
            $this->rawData,
            true,
            512,
            JSON_THROW_ON_ERROR
        )['result'] ?? [];
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
