<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\CommonOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\Regular\ImageOutgoingMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;

class ImageApiMessage extends BaseApiMessage
{
    public static function canCreateFromMessage(CommonOutgoingMessageInterface $outgoingMessage): bool
    {
        return $outgoingMessage instanceof ImageOutgoingMessageInterface;
    }

    protected function send(HttpClientInterface $client, CommonOutgoingMessageInterface $outgoingMessage, array $data): array
    {
        /** @var ImageOutgoingMessageInterface $outgoingMessage */
        return $client->postJson(
            'sendPhoto',
            array_merge($data, [
                'photo' => $outgoingMessage->getImageUrl(),
                'caption' => $outgoingMessage->getCaption(),
            ])
        )->getParsedDataResult();
    }
}
