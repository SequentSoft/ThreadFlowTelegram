<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\BasicOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\Regular\ForwardOutgoingMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;

class ForwardApiMessage extends BaseApiMessage
{
    public static function canCreateFromMessage(BasicOutgoingMessageInterface $outgoingMessage): bool
    {
        return $outgoingMessage instanceof ForwardOutgoingMessageInterface;
    }

    protected function send(HttpClientInterface $client, BasicOutgoingMessageInterface $outgoingMessage, array $data): array
    {
        /** @var ForwardOutgoingMessageInterface $outgoingMessage */
        return $client->postJson(
            'forwardMessage',
            array_merge($data, [
                'from_chat_id' => $outgoingMessage->getTargetMessage()->getContext()->getRoom()->getId(),
            ])
        )->getParsedDataResult();
    }
}
