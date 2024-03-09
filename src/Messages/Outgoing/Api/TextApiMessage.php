<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Outgoing\Api;

use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\CommonOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\Regular\HtmlOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\Regular\MarkdownOutgoingMessageInterface;
use SequentSoft\ThreadFlow\Contracts\Messages\Outgoing\Regular\TextOutgoingMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\HttpClient\HttpClientInterface;

class TextApiMessage extends BaseApiMessage
{
    public static function canCreateFromMessage(CommonOutgoingMessageInterface $outgoingMessage): bool
    {
        return $outgoingMessage instanceof TextOutgoingMessageInterface
            || $outgoingMessage instanceof HtmlOutgoingMessageInterface
            || $outgoingMessage instanceof MarkdownOutgoingMessageInterface;
    }

    protected function send(HttpClientInterface $client, CommonOutgoingMessageInterface $outgoingMessage, array $data): array
    {
        if ($outgoingMessage instanceof HtmlOutgoingMessageInterface) {
            return $client->postJson(
                'sendMessage',
                array_merge($data, array_filter([
                    'text' => $outgoingMessage->getHtml(),
                    'parse_mode' => 'HTML',
                ]))
            )->getParsedDataResult();
        }

        if ($outgoingMessage instanceof MarkdownOutgoingMessageInterface) {
            return $client->postJson(
                'sendMessage',
                array_merge($data, array_filter([
                    'text' => $outgoingMessage->getMarkdown(),
                    'parse_mode' => 'MarkdownV2',
                ]))
            )->getParsedDataResult();
        }

        if (! $outgoingMessage instanceof TextOutgoingMessageInterface) {
            throw new \InvalidArgumentException('Unsupported message type');
        }

        return $client->postJson(
            'sendMessage',
            array_merge($data, array_filter([
                'text' => $outgoingMessage->getText(),
            ]))
        )->getParsedDataResult();
    }
}
