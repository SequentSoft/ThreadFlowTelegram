<?php

namespace SequentSoft\ThreadFlowTelegram\Contracts\Messages\Outgoing;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\OutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Page\PageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;

interface ApiMessageInterface
{
    public static function canCreateFromMessage(OutgoingMessageInterface $outgoingMessage): bool;

    public static function createFromMessage(
        OutgoingMessageInterface $outgoingMessage,
        ?PageInterface $contextPage = null
    ): static;

    public function sendVia(HttpClientInterface $client): array;
}
