<?php

namespace SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming;

interface WithApiTokenInterface
{
    public function getApiToken(): string;

    public function setApiToken(string $apiToken): void;
}
