<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\OutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\Regular\ForwardOutgoingRegularMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;

class ForwardApiMessage extends BaseApiMessage
{
    public static function canCreateFromMessage(OutgoingMessageInterface $outgoingMessage): bool
    {
        return $outgoingMessage instanceof ForwardOutgoingRegularMessageInterface;
    }

    protected function send(HttpClientInterface $client, OutgoingMessageInterface $outgoingMessage, array $data): array
    {
        /** @var ForwardOutgoingRegularMessageInterface $outgoingMessage */
        return $client->postJson(
            'forwardMessage',
            array_merge($data, [
                'from_chat_id' => $outgoingMessage->getTargetMessage()->getContext()->getRoom()->getId(),
            ])
        )->getParsedData();
    }
}
