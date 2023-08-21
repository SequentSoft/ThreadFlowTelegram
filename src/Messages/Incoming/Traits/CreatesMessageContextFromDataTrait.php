<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits;

use SequentSoft\ThreadFlow\Chat\MessageContext;
use SequentSoft\ThreadFlow\Contracts\Channel\Incoming\IncomingChannelInterface;
use SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming\IncomingMessagesFactoryInterface;

trait CreatesMessageContextFromDataTrait
{
    use CreatesParticipantFromDataTrait;
    use CreatesRoomFromDataTrait;
    use CreatesForwardParticipantFromDataTrait;

    public static function createMessageContextFromData(
        array $data,
        IncomingMessagesFactoryInterface $factory
    ): MessageContext {
        return new MessageContext(
            static::createParticipantFromData($data),
            static::createRoomFromData($data),
            static::createForwardParticipantFromData($data),
            isset($data['message']['reply_to_message']['message_id'])
                ? $factory->make([
                    'message' => $data['message']['reply_to_message'],
                ])
                : null,
        );
    }
}
