<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\OutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\Regular\ImageOutgoingRegularMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;

class ImageApiMessage extends BaseApiMessage
{
    public static function canCreateFromMessage(OutgoingMessageInterface $outgoingMessage): bool
    {
        return $outgoingMessage instanceof ImageOutgoingRegularMessageInterface;
    }

    protected function send(HttpClientInterface $client, OutgoingMessageInterface $outgoingMessage, array $data): array
    {
        /** @var ImageOutgoingRegularMessageInterface $outgoingMessage */
        return $client->postJson(
            'sendPhoto',
            array_merge($data, [
                'photo' => $outgoingMessage->getImageUrl(),
                'caption' => $outgoingMessage->getCaption(),
            ])
        )->getParsedData();
    }
}
