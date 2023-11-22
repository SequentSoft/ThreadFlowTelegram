<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\OutgoingMessageInterface;
use SequentSoft\ThreadFlow\Messages\Outgoing\Regular\TextOutgoingMessage;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;

class TextApiMessage extends BaseApiMessage
{
    public static function canCreateFromMessage(OutgoingMessageInterface $outgoingMessage): bool
    {
        return $outgoingMessage instanceof TextOutgoingMessage;
    }

    protected function send(HttpClientInterface $client, OutgoingMessageInterface $outgoingMessage, array $data): array
    {
        /** @var TextOutgoingMessage $outgoingMessage */
        return $client->postJson(
            'sendMessage',
            array_merge($data, [
                'text' => $outgoingMessage->getText(),
            ])
        )->getParsedDataResult();
    }
}
