<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular;

use DateTimeImmutable;
use SequentSoft\ThreadFlow\Contracts\Channel\Incoming\IncomingChannelInterface;
use SequentSoft\ThreadFlow\Messages\Incoming\Regular\ContactIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\CanCreateFromDataMessageInterface;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits\CreatesMessageContextFromDataTrait;

class TelegramContactIncomingRegularMessage extends ContactIncomingRegularMessage implements
    CanCreateFromDataMessageInterface
{
    use CreatesMessageContextFromDataTrait;

    public static function canCreateFromData(array $data): bool
    {
        return isset($data['message']['contact']);
    }

    public static function createFromData(IncomingChannelInterface $channel, array $data): self
    {
        $message = new static(
            id: $data['message']['message_id'],
            context: static::createMessageContextFromData($data),
            timestamp: DateTimeImmutable::createFromFormat('U', $data['message']['date']),
            phoneNumber: $data['message']['contact']['phone_number'],
            firstName: $data['message']['contact']['first_name'] ?? '',
            lastName: $data['message']['contact']['last_name'] ?? '',
            userId: $data['message']['contact']['user_id'] ?? '',
        );

        $message->setRaw($data);

        return $message;
    }
}
