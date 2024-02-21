<?php

namespace SequentSoft\ThreadFlowTelegram\Contracts\Messages\Outgoing;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\CommonOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Page\PageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;

interface ApiMessageInterface
{
    public static function canCreateFromMessage(CommonOutgoingMessageInterface $outgoingMessage): bool;

    public static function createFromMessage(
        CommonOutgoingMessageInterface $outgoingMessage,
        ?PageInterface                 $contextPage = null
    ): static;

    public function sendVia(HttpClientInterface $client): array;
}
