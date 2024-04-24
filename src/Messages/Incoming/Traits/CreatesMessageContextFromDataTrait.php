<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits;

use SequentSoft\ThreadFlow\Chat\MessageContext;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;

trait CreatesMessageContextFromDataTrait
{
    use CreatesParticipantFromDataTrait;
    use CreatesRoomFromDataTrait;
    use CreatesForwardParticipantFromDataTrait;

    public static function createMessageContextFromData(
        string $channelName,
        array $data,
        IncomingMessagesFactoryInterface $factory
    ): MessageContext {
        return new MessageContext(
            $channelName,
            static::createParticipantFromData($data),
            static::createRoomFromData($data),
        );
    }
}
