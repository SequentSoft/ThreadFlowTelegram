<?php

namespace SequentSoft\ThreadFlowTelegram\HttpClient;

use JsonException;
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

    /**
     * @throws JsonException
     */
    public function getParsedData(): array
    {
        return json_decode(
            $this->rawData,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * @throws JsonException
     */
    public function getParsedDataResult(): mixed
    {
        return $this->getParsedData()['result'] ?? [];
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
