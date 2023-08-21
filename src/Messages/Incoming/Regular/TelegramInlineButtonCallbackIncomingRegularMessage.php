<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Regular;

use DateTimeImmutable;
use SequentSoft\ThreadFlow\Contracts\Channel\Incoming\IncomingChannelInterface;
use SequentSoft\ThreadFlow\Messages\Incoming\Regular\TextIncomingRegularMessage;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\CanCreateFromDataMessageInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits\CreatesMessageContextFromDataTrait;

class TelegramInlineButtonCallbackIncomingRegularMessage extends TelegramTextIncomingRegularMessage
{
    use CreatesMessageContextFromDataTrait;

    public static function canCreateFromData(array $data): bool
    {
        return isset($data['callback_query']);
    }

    public static function createFromData(IncomingMessagesFactoryInterface $factory, array $data): self
    {
        $exploded = explode(':', $data['callback_query']['data'], 2);
        $stateId = null;

        if (count($exploded) === 2) {
            $stateId = $exploded[0];
            $data['callback_query']['data'] = $exploded[1];
        } else {
            $data['callback_query']['data'] = $exploded[0];
        }

        $message = new static(
            id: $data['callback_query']['id'],
            context: static::createMessageContextFromData($data['callback_query'], $factory),
            timestamp: new DateTimeImmutable(),
            text: $data['callback_query']['data'],
        );

        $message->setStateId($stateId);
        $message->setRaw($data);

        return $message;
    }
}
