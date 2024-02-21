<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular;

use DateTimeImmutable;
use SequentSoft\ThreadFlow\Messages\Incoming\Regular\TextIncomingMessage;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\CanCreateFromDataMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits\CreatesMessageContextFromDataTrait;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits\WithMessageReactions;

class TelegramTextIncomingMessage extends TextIncomingMessage implements CanCreateFromDataMessageInterface
{
    use CreatesMessageContextFromDataTrait;
    use WithMessageReactions;

    public static function canCreateFromData(array $data): bool
    {
        return isset($data['message']['text']);
    }

    public static function createFromData(IncomingMessagesFactoryInterface $factory, string $channelName, array $data): self
    {
        return new static(
            id: $data['message']['message_id'],
            context: static::createMessageContextFromData($channelName, $data, $factory),
            timestamp: DateTimeImmutable::createFromFormat('U', $data['message']['date']),
            text: $data['message']['text'],
        );
    }
}
