<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\BasicOutgoingMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;
use SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Service\TelegramMessageReactionOutgoingServiceMessage;

class MessageReactionApiMessage extends BaseApiMessage
{
    public static function canCreateFromMessage(BasicOutgoingMessageInterface $outgoingMessage): bool
    {
        return $outgoingMessage instanceof TelegramMessageReactionOutgoingServiceMessage;
    }

    protected function send(HttpClientInterface $client, BasicOutgoingMessageInterface $outgoingMessage, array $data): array
    {
        /** @var TelegramMessageReactionOutgoingServiceMessage $outgoingMessage */
        $client->postJson(
            'setMessageReaction',
            array_merge($data, [
                'message_id' => $outgoingMessage->getTargetMessageId(),
                'reaction' => [
                    [
                        'type' => $outgoingMessage->getType(),
                        'emoji' => $outgoingMessage->getReaction()->value,
                    ],
                ],
                'is_big' => $outgoingMessage->isBig(),
            ])
        );

        return [];
    }
}
