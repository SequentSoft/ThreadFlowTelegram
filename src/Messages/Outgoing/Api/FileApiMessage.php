<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\BasicOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\Regular\FileOutgoingMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;

class FileApiMessage extends BaseApiMessage
{
    public static function canCreateFromMessage(BasicOutgoingMessageInterface $outgoingMessage): bool
    {
        return $outgoingMessage instanceof FileOutgoingMessageInterface;
    }

    protected function send(HttpClientInterface $client, BasicOutgoingMessageInterface $outgoingMessage, array $data): array
    {
        /** @var FileOutgoingMessageInterface $outgoingMessage */

        // local file
        if ($outgoingMessage->getPath() !== null) {
            if (! empty($data['reply_markup'])) {
                $data['reply_markup'] = json_encode($data['reply_markup']);
            }

            return $client->postMultipart('sendDocument', [
                ['name' => 'chat_id', 'contents' => $data['chat_id']],
                ['name' => 'document', 'contents' => fopen($outgoingMessage->getPath(), 'rb')],
                ['name' => 'caption', 'contents' => $outgoingMessage->getCaption()],
                ['name' => 'reply_markup', 'contents' => $data['reply_markup'] ?? null],
            ])->getParsedDataResult();
        }

        // url file
        return $client->postJson(
            'sendDocument',
            array_merge($data, [
                'document' => $outgoingMessage->getUrl(),
                'caption' => $outgoingMessage->getCaption(),
            ])
        )->getParsedDataResult();
    }
}
