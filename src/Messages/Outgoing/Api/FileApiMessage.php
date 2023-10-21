<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\OutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\Regular\FileOutgoingRegularMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;

class FileApiMessage extends BaseApiMessage
{
    public static function canCreateFromMessage(OutgoingMessageInterface $outgoingMessage): bool
    {
        return $outgoingMessage instanceof FileOutgoingRegularMessageInterface;
    }

    protected function send(HttpClientInterface $client, OutgoingMessageInterface $outgoingMessage, array $data): array
    {
        /** @var FileOutgoingRegularMessageInterface $outgoingMessage */

        // local file
        if ($outgoingMessage->getPath() !== null) {
            return $client->postMultipart('sendDocument', [
                ['name' => 'chat_id', 'contents' => $data['chat_id']],
                ['name' => 'document', 'contents' => fopen($outgoingMessage->getPath(), 'rb')],
                ['name' => 'caption', 'contents' => $outgoingMessage->getCaption()],
                ['name' => 'reply_markup', 'contents' => $data['reply_markup'] ?? null],
            ])->getParsedData();
        }

        // url file
        return $client->postJson(
            'sendDocument',
            array_merge($data, [
                'document' => $outgoingMessage->getUrl(),
                'caption' => $outgoingMessage->getCaption(),
            ])
        )->getParsedData();
    }
}
