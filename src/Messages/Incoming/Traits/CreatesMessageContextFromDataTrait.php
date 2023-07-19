<?php

namespace SequentSoft\ThreadFlowTelegram\Messages\Incoming\Traits;

use SequentSoft\ThreadFlow\Chat\MessageContext;

trait CreatesMessageContextFromDataTrait
{
    use CreatesParticipantFromDataTrait;
    use CreatesRoomFromDataTrait;
    use CreatesForwardParticipantFromDataTrait;

    public static function createMessageContextFromData(array $data): MessageContext
    {
        return new MessageContext(
            static::createParticipantFromData($data),
            static::createRoomFromData($data),
            static::createForwardParticipantFromData($data),
        );
    }
}
