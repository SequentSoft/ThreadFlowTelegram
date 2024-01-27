<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Service;

use DateTimeImmutable;
use SequentSoft\ThreadFlow\Messages\Incoming\Service\BotStartedIncomingServiceMessage;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;
use SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits\CreatesMessageContextFromDataTrait;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\CanCreateFromDataMessageInterface;

class TelegramBotStartedIncomingServiceMessage extends BotStartedIncomingServiceMessage implements
    CanCreateFromDataMessageInterface
{
    use CreatesMessageContextFromDataTrait;

    public static function canCreateFromData(array $data): bool
    {
        return isset($data['message']['text'])
            && $data['message']['text'] === '/start';
    }

    public static function createFromData(IncomingMessagesFactoryInterface $factory, string $channelName, array $data): self
    {
        $message = new self(
            id: $data['message']['message_id'],
            context: self::createMessageContextFromData($channelName, $data, $factory),
            timestamp: DateTimeImmutable::createFromFormat('U', $data['message']['date']),
        );

        $message->setRaw($data);

        return $message;
    }
}
