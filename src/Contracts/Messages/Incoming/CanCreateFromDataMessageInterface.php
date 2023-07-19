<?php

namespace SequentSoft\ThreadFlowTelegram\Contracts\Messages\Incoming;

use SequentSoft\ThreadFlow\Contracts\Channel\Incoming\IncomingChannelInterface;

interface CanCreateFromDataMessageInterface
{
    public static function canCreateFromData(array $data): bool;

    public static function createFromData(
        IncomingChannelInterface $channel,
        IncomingMessagesFactoryInterface $factory,
        array $data
    ): self;
}
