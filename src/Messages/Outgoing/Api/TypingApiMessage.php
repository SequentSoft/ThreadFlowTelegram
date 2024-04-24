<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\BasicOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\Service\TypingOutgoingServiceMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;

class TypingApiMessage extends BaseApiMessage
{
    public static function canCreateFromMessage(BasicOutgoingMessageInterface $outgoingMessage): bool
    {
        return $outgoingMessage instanceof TypingOutgoingServiceMessageInterface;
    }

    protected function send(HttpClientInterface $client, BasicOutgoingMessageInterface $outgoingMessage, array $data): array
    {
        $client->postJson(
            'sendChatAction',
            array_merge($data, [
                'action' => 'typing',
            ])
        );

        return [];
    }
}
