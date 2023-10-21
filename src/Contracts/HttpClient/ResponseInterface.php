<?php

namespace SequentSoft\ThreadFlowTelegram\Contracts\HttpClient;

interface ResponseInterface
{
    public function getRawData(): string;
    public function getParsedData(): array;
    public function getStatusCode(): int;
}
