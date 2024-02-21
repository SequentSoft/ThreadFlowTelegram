<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\CommonOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Messages\Outgoing\Regular\TextOutgoingMessage;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;
use SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Regular\TelegramTextOutgoingMessage;

class TextApiMessage extends BaseApiMessage
{
    public static function canCreateFromMessage(CommonOutgoingMessageInterface $outgoingMessage): bool
    {
        return $outgoingMessage instanceof TextOutgoingMessage;
    }

    protected function send(HttpClientInterface $client, CommonOutgoingMessageInterface $outgoingMessage, array $data): array
    {
        if ($outgoingMessage instanceof TelegramTextOutgoingMessage) {
            $parseMode = $outgoingMessage->getParseMode();
        } else {
            $parseMode = null;
        }

        /** @var TextOutgoingMessage $outgoingMessage */
        return $client->postJson(
            'sendMessage',
            array_merge($data, array_filter([
                'text' => $outgoingMessage->getText(),
                'parse_mode' => $parseMode,
            ]))
        )->getParsedDataResult();
    }
}
