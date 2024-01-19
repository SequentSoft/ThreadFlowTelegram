<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular;

use DateTimeImmutable;
use SequentSoft\ThreadFlow\Messages\Incoming\Regular\TextIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\CanCreateFromDataMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits\CreatesMessageContextFromDataTrait;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits\WithMessageReactions;

class TelegramTextIncomingRegularMessage extends TextIncomingRegularMessage implements CanCreateFromDataMessageInterface
{
    use CreatesMessageContextFromDataTrait;
    use WithMessageReactions;

    public static function canCreateFromData(array $data): bool
    {
        return isset($data['message']['text']);
    }

    public static function createFromData(IncomingMessagesFactoryInterface $factory, array $data): self
    {
        $message = new static(
            id: $data['message']['message_id'],
            context: static::createMessageContextFromData($data, $factory),
            timestamp: DateTimeImmutable::createFromFormat('U', $data['message']['date']),
            text: $data['message']['text'],
        );

        $message->setRaw($data);

        return $message;
    }
}
